@extends('admin.layouts.admin')

@section('title', 'Recycle Bin')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-trash3 text-danger me-2"></i> Recycle Bin</h5>
            </div>
            <div class="card-body">
                <!-- Modules Navigation -->
                <ul class="nav nav-pills mb-4 pb-2 border-bottom">
                    @foreach($modules as $key => $module)
                        <li class="nav-item me-2 mb-2">
                            <a class="nav-link {{ $currentModule === $key ? 'active' : 'bg-light text-dark' }}" 
                               href="{{ route('admin.recycle-bin.index', ['module' => $key]) }}">
                                {{ $module['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if($trashedItems->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-emoji-smile fs-1 mb-3 d-block text-secondary"></i>
                        <h5>No deleted items found in {{ $modules[$currentModule]['label'] }}</h5>
                        <p>Items deleted from this module will appear here.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>{{ $titleField === 'name_en' ? 'Name' : ($titleField === 'title_en' ? 'Title' : 'Item') }}</th>
                                    <th>Deleted At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trashedItems as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td class="fw-medium">{{ $item->{$titleField} ?? 'Unnamed Item' }}</td>
                                    <td>
                                        <span class="text-danger">
                                            {{ $item->deleted_at->format('M d, Y h:i A') }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $item->deleted_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-end">
                                        <!-- Restore Form -->
                                        <form action="{{ route('admin.recycle-bin.restore') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="module" value="{{ $currentModule }}">
                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                            <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>

                                        <!-- Permanent Delete Form -->
                                        <form action="{{ route('admin.recycle-bin.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('WARNING: This will permanently delete the item. This action cannot be undone. Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="module" value="{{ $currentModule }}">
                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                            <button type="submit" class="btn btn-sm btn-danger ms-1" title="Delete Permanently">
                                                <i class="bi bi-x-circle"></i> Permanent Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

