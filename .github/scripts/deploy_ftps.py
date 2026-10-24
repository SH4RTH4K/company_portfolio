"""Deliver a prepared Laravel release using verified explicit TLS FTPS."""

import ftplib
import hashlib
import io
import json
import os
import posixpath
import re
import ssl
import stat
import sys
import zipfile


ALLOWED_ROOTS = {"app", "bootstrap", "config", "database", "public", "resources", "lang", "routes", "vendor"}
ALLOWED_FILES = {"artisan", "composer.json", "composer.lock", "deployment-manifest.json"}


def safe_relative_path(path):
    if not path or path.startswith("/") or "\\" in path or any(ord(char) < 32 for char in path):
        raise ValueError("The release contains an unsafe file path.")
    parts = path.split("/")
    if any(part in {"", ".", "..", ".git", "node_modules"} or part == ".env" or part.startswith(".env.") for part in parts):
        raise ValueError("The release contains a protected or unsafe file path.")
    if parts[0] not in ALLOWED_ROOTS and path not in ALLOWED_FILES:
        raise ValueError("The release contains a file outside the application paths.")
    if path == "bootstrap/cache" or path.startswith("bootstrap/cache/"):
        raise ValueError("Runtime cache files must not be uploaded.")
    if any(path == prefix or path.startswith(prefix + "/") for prefix in ("public/storage", "public/uploads", "public/hot")):
        raise ValueError("Uploads and development assets must not be overwritten.")
    return path


def remote_directory(value):
    if not value or "\\" in value or any(ord(char) < 32 for char in value):
        raise ValueError("Set FTPS_DIRECTORY to the existing application root; its public directory must be the document root.")
    if any(part in {".", ".."} for part in value.split("/")):
        raise ValueError("FTPS_DIRECTORY must not contain traversal.")
    normalized = posixpath.normpath(value)
    if normalized in {"/", "."}:
        raise ValueError("FTPS_DIRECTORY must identify an explicit application directory, not the account root.")
    return normalized


def release_entries(archive, expected_commit):
    entries = {}
    for entry in archive.infolist():
        if entry.is_dir():
            continue
        path = safe_relative_path(entry.filename)
        if path in entries or stat.S_ISLNK(entry.external_attr >> 16):
            raise ValueError("Duplicate files and symbolic links are not supported.")
        entries[path] = entry
    manifest = json.loads(archive.read(entries["deployment-manifest.json"]))
    if manifest.get("version") != 1 or manifest.get("commit") != expected_commit:
        raise ValueError("The deployment manifest does not match the confirmed commit.")
    hashes = manifest.get("files", {})
    if set(hashes) != set(entries) - {"deployment-manifest.json"}:
        raise ValueError("The release file list differs from its manifest.")
    for path, digest in hashes.items():
        if not isinstance(digest, str) or hashlib.sha256(archive.read(entries[path])).hexdigest() != digest:
            raise ValueError("The release contains a file with an invalid checksum.")
    return entries


def ensure_directory(client, path):
    if path.startswith("/"):
        client.cwd("/")
    for segment in path.strip("/").split("/"):
        if not segment:
            continue
        try:
            client.cwd(segment)
        except ftplib.error_perm:
            client.mkd(segment)
            client.cwd(segment)


def deliver(client, archive, entries, root, commit):
    client.cwd(root)
    application_root = client.pwd()
    directories = {application_root}
    ordered = sorted(path for path in entries if path != "deployment-manifest.json")
    ordered.append("deployment-manifest.json")
    for path in ordered:
        directory = posixpath.join(application_root, posixpath.dirname(path))
        if directory not in directories:
            ensure_directory(client, directory)
            directories.add(directory)
        client.cwd(directory)
        basename = posixpath.basename(path)
        temporary = ".deployment-" + commit[:12] + "-" + basename
        client.storbinary("STOR " + temporary, io.BytesIO(archive.read(entries[path])))
        client.rename(temporary, basename)
    client.cwd(application_root)


def main():
    root = remote_directory(os.environ.get("FTPS_DIRECTORY", ""))
    commit = os.environ.get("RELEASE_COMMIT", "")
    if not re.fullmatch(r"[a-f0-9]{40}", commit):
        raise ValueError("A full confirmed release commit is required.")
    host, user, password = (os.environ.get(key, "") for key in ("FTPS_HOST", "FTPS_USER", "FTPS_PASSWORD"))
    if not all((host, user, password)) or any(ord(char) < 32 for char in host + user):
        raise ValueError("Configure FTPS_HOST, FTPS_USER, and FTPS_PASSWORD in the GitHub production environment.")
    port = int(os.environ.get("FTPS_PORT") or "21")
    if not 1 <= port <= 65535:
        raise ValueError("The FTPS port is invalid.")
    with open(sys.argv[1], "rb") as handle:
        digest = hashlib.file_digest(handle, "sha256").hexdigest()
    with open(sys.argv[2], encoding="utf-8") as handle:
        expected = handle.read().strip()
    if expected != digest + "  release.zip":
        raise ValueError("The release ZIP checksum is invalid.")
    with zipfile.ZipFile(sys.argv[1]) as archive:
        entries = release_entries(archive, commit)
        context = ssl.create_default_context()
        with ftplib.FTP_TLS(context=context, timeout=60) as client:
            client.connect(host, port)
            client.login(user, password)
            client.prot_p()
            deliver(client, archive, entries, root, commit)
    print("Release uploaded. Return to /git-repository to finalize database checks and caches.")


if __name__ == "__main__":
    try:
        main()
    except Exception:
        print("FTPS delivery failed. Check the production environment settings, TLS certificate, directory permissions, and hosting maintenance state. No files were deleted. Restore the prior release package if necessary.", file=sys.stderr)
        sys.exit(1)
