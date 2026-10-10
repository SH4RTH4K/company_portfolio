@php
    $seo = isset($model) ? $model->seoMeta : null;
@endphp

<div class="row g-4">
    <!-- Left Column: Inputs -->
    <div class="col-lg-7">
        <!-- English SEO -->
        <div class="p-3 bg-white border rounded-3 mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-translate me-1"></i> English SEO Meta</h6>
                <span class="badge bg-light text-dark border">EN</span>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label small fw-semibold">SEO Title (EN)</label>
                    <span id="title_en_count" class="small text-muted">0 / 60</span>
                </div>
                <input type="text" id="seo_title_en" name="seo_title_en" class="form-control" value="{{ old('seo_title_en', $seo->seo_title_en ?? '') }}" maxlength="70" placeholder="Ideal: 50-60 characters">
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label small fw-semibold">Meta Description (EN)</label>
                    <span id="desc_en_count" class="small text-muted">0 / 160</span>
                </div>
                <textarea id="seo_description_en" name="seo_description_en" class="form-control" rows="3" maxlength="180" placeholder="Ideal: 140-160 characters">{{ old('seo_description_en', $seo->seo_description_en ?? '') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Focus Keyword (EN)</label>
                <input type="text" id="focus_keyword_en" name="focus_keyword_en" class="form-control" value="{{ old('focus_keyword_en', $seo->focus_keyword_en ?? '') }}" placeholder="e.g. Managed IT Services Dhaka">
            </div>
        </div>

        <!-- Bengali SEO -->
        <div class="p-3 bg-white border rounded-3 mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-translate me-1"></i> বাংলা এসইও মেটা (Bengali SEO)</h6>
                <span class="badge bg-light text-dark border">BN</span>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label small fw-semibold">এসইও টাইটেল (BN)</label>
                    <span id="title_bn_count" class="small text-muted">0 / 60</span>
                </div>
                <input type="text" id="seo_title_bn" name="seo_title_bn" class="form-control" value="{{ old('seo_title_bn', $seo->seo_title_bn ?? '') }}" maxlength="70">
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label small fw-semibold">মেটা বিবরণ (BN)</label>
                    <span id="desc_bn_count" class="small text-muted">0 / 160</span>
                </div>
                <textarea id="seo_description_bn" name="seo_description_bn" class="form-control" rows="3" maxlength="180">{{ old('seo_description_bn', $seo->seo_description_bn ?? '') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">ফোকাস কীওয়ার্ড (BN)</label>
                <input type="text" id="focus_keyword_bn" name="focus_keyword_bn" class="form-control" value="{{ old('focus_keyword_bn', $seo->focus_keyword_bn ?? '') }}">
            </div>
        </div>

        <!-- Indexing & Canonical -->
        <div class="p-3 bg-white border rounded-3 shadow-sm">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear-wide-connected me-1"></i> Robots & Canonical</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="robots_index" value="0">
                        <input class="form-check-input" type="checkbox" id="robots_index" name="robots_index" value="1" {{ old('robots_index', $seo->robots_index ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="robots_index">Allow Indexing (index)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="robots_follow" value="0">
                        <input class="form-check-input" type="checkbox" id="robots_follow" name="robots_follow" value="1" {{ old('robots_follow', $seo->robots_follow ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="robots_follow">Follow Links (follow)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Canonical Override (EN)</label>
                        <input type="text" name="canonical_url_en" class="form-control form-control-sm" value="{{ old('canonical_url_en', $seo->canonical_url_en ?? '') }}" placeholder="Default is current URL">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Canonical Override (BN)</label>
                        <input type="text" name="canonical_url_bn" class="form-control form-control-sm" value="{{ old('canonical_url_bn', $seo->canonical_url_bn ?? '') }}" placeholder="Default is current URL">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Google SERP Preview & Internal SEO Score -->
    <div class="col-lg-5">
        <!-- Live Google Search Preview -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-google text-primary me-2"></i> Live Google Search Preview</h6>
                <span class="badge bg-light text-muted border">Desktop</span>
            </div>
            <div class="card-body p-4 bg-light">
                <!-- Google SERP Snippet Box -->
                <div class="p-3 bg-white rounded border shadow-sm font-sans" style="font-family: Arial, sans-serif;">
                    <div class="d-flex align-items-center mb-1">
                        <div class="rounded-circle bg-light border p-1 d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="bi bi-globe small text-muted"></i>
                        </div>
                        <div class="text-truncate">
                            <span class="text-dark small fw-normal d-block lh-1" style="font-size: 13px;">Zero Gravities</span>
                            <span id="preview_url" class="text-muted small" style="font-size: 11px;">https://zerogravities.com/en/...</span>
                        </div>
                    </div>
                    <div id="preview_title" class="text-primary fw-normal mb-1 fs-5 text-truncate" style="color: #1a0dab !important; cursor: pointer; text-decoration: none;">
                        Zero Gravities | Technology & Solutions
                    </div>
                    <div id="preview_desc" class="text-muted small lh-sm" style="color: #4d5156 !important; font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        Please enter an SEO title and description to preview how your page snippet appears on search engines.
                    </div>
                </div>
            </div>

            <!-- Internal SEO Quality Score Widget -->
            <div class="card-footer bg-white p-4 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark small">Internal SEO Quality Score</span>
                    <span id="seo_score_badge" class="badge bg-secondary fs-6 px-3 py-1">0 / 100</span>
                </div>
                
                <div class="progress mb-3" style="height: 8px;">
                    <div id="seo_score_bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <!-- Real-time Checklist -->
                <ul class="list-unstyled small mb-0" id="seo_checklist">
                    <li class="mb-1 text-muted" id="check_title"><i class="bi bi-circle me-2 text-muted"></i> SEO Title length (50–60 chars)</li>
                    <li class="mb-1 text-muted" id="check_desc"><i class="bi bi-circle me-2 text-muted"></i> Meta Description length (140–160 chars)</li>
                    <li class="mb-1 text-muted" id="check_kw"><i class="bi bi-circle me-2 text-muted"></i> Focus keyword specified</li>
                    <li class="mb-1 text-muted" id="check_kw_title"><i class="bi bi-circle me-2 text-muted"></i> Keyword present in SEO Title</li>
                    <li class="text-muted" id="check_kw_desc"><i class="bi bi-circle me-2 text-muted"></i> Keyword present in Meta Description</li>
                </ul>

                <input type="hidden" name="seo_score_en" id="seo_score_en_input" value="{{ old('seo_score_en', $seo->seo_score_en ?? 0) }}">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleEn = document.getElementById('seo_title_en');
    const descEn = document.getElementById('seo_description_en');
    const kwEn = document.getElementById('focus_keyword_en');
    
    const titleBn = document.getElementById('seo_title_bn');
    const descBn = document.getElementById('seo_description_bn');
    
    const titleEnCount = document.getElementById('title_en_count');
    const descEnCount = document.getElementById('desc_en_count');
    const titleBnCount = document.getElementById('title_bn_count');
    const descBnCount = document.getElementById('desc_bn_count');
    
    const previewTitle = document.getElementById('preview_title');
    const previewDesc = document.getElementById('preview_desc');
    
    const scoreBadge = document.getElementById('seo_score_badge');
    const scoreBar = document.getElementById('seo_score_bar');
    const scoreInput = document.getElementById('seo_score_en_input');

    function updateCountersAndScore() {
        const tVal = titleEn ? titleEn.value.trim() : '';
        const dVal = descEn ? descEn.value.trim() : '';
        const kVal = kwEn ? kwEn.value.trim().toLowerCase() : '';

        // Lengths
        if (titleEnCount) titleEnCount.textContent = `${tVal.length} / 60`;
        if (descEnCount) descEnCount.textContent = `${dVal.length} / 160`;
        
        if (titleBn && titleBnCount) titleBnCount.textContent = `${titleBn.value.trim().length} / 60`;
        if (descBn && descBnCount) descBnCount.textContent = `${descBn.value.trim().length} / 160`;

        // Preview snippet
        if (previewTitle) {
            previewTitle.textContent = tVal.length > 0 ? tVal : 'Zero Gravities | Technology & Solutions';
        }
        if (previewDesc) {
            previewDesc.textContent = dVal.length > 0 ? dVal : 'Please enter an SEO title and description to preview how your page snippet appears on search engines.';
        }

        // Score Calculation
        let score = 0;
        
        // 1. Title check (25 pts)
        const checkTitle = document.getElementById('check_title');
        if (tVal.length >= 40 && tVal.length <= 65) {
            score += 25;
            setCheck(checkTitle, true, 'Ideal Title Length (' + tVal.length + ' chars)');
        } else if (tVal.length > 0) {
            score += 12;
            setCheck(checkTitle, false, 'Title Length (' + tVal.length + ' chars — aim for 50-60)');
        } else {
            setCheck(checkTitle, false, 'Missing SEO Title');
        }

        // 2. Desc check (25 pts)
        const checkDesc = document.getElementById('check_desc');
        if (dVal.length >= 130 && dVal.length <= 165) {
            score += 25;
            setCheck(checkDesc, true, 'Ideal Description Length (' + dVal.length + ' chars)');
        } else if (dVal.length > 0) {
            score += 12;
            setCheck(checkDesc, false, 'Description Length (' + dVal.length + ' chars — aim for 140-160)');
        } else {
            setCheck(checkDesc, false, 'Missing Meta Description');
        }

        // 3. Keyword defined (20 pts)
        const checkKw = document.getElementById('check_kw');
        if (kVal.length > 2) {
            score += 20;
            setCheck(checkKw, true, 'Focus Keyword configured');
        } else {
            setCheck(checkKw, false, 'Focus Keyword missing');
        }

        // 4. Keyword in Title (15 pts)
        const checkKwTitle = document.getElementById('check_kw_title');
        if (kVal.length > 2 && tVal.toLowerCase().includes(kVal)) {
            score += 15;
            setCheck(checkKwTitle, true, 'Keyword present in SEO Title');
        } else {
            setCheck(checkKwTitle, false, 'Keyword missing from Title');
        }

        // 5. Keyword in Desc (15 pts)
        const checkKwDesc = document.getElementById('check_kw_desc');
        if (kVal.length > 2 && dVal.toLowerCase().includes(kVal)) {
            score += 15;
            setCheck(checkKwDesc, true, 'Keyword present in Meta Description');
        } else {
            setCheck(checkKwDesc, false, 'Keyword missing from Description');
        }

        // Update score UI
        if (scoreBadge) {
            scoreBadge.textContent = `${score} / 100`;
            scoreBadge.className = 'badge fs-6 px-3 py-1 ' + (score >= 80 ? 'bg-success' : (score >= 50 ? 'bg-warning text-dark' : 'bg-danger'));
        }
        if (scoreBar) {
            scoreBar.style.width = `${score}%`;
            scoreBar.className = 'progress-bar ' + (score >= 80 ? 'bg-success' : (score >= 50 ? 'bg-warning' : 'bg-danger'));
        }
        if (scoreInput) {
            scoreInput.value = score;
        }
    }

    function setCheck(element, isPassed, text) {
        if (!element) return;
        element.innerHTML = isPassed 
            ? `<i class="bi bi-check-circle-fill text-success me-2"></i> <span class="text-dark fw-medium">${text}</span>`
            : `<i class="bi bi-x-circle text-danger me-2"></i> <span class="text-muted">${text}</span>`;
    }

    if (titleEn) titleEn.addEventListener('input', updateCountersAndScore);
    if (descEn) descEn.addEventListener('input', updateCountersAndScore);
    if (kwEn) kwEn.addEventListener('input', updateCountersAndScore);
    if (titleBn) titleBn.addEventListener('input', updateCountersAndScore);
    if (descBn) descBn.addEventListener('input', updateCountersAndScore);

    // Initial run
    updateCountersAndScore();
});
</script>
