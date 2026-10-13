@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('page', 'media')

@section('content')
<div class="page-head">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item active">Media Library</li>
            </ol>
        </nav>
        <h1>Media Library</h1>
    </div>
</div>

<section class="panel">

    {{-- ── Filter Bar ─────────────────────────────────────────────────── --}}
    <div class="row g-2 align-items-end mb-3">

        {{-- Search --}}
        <div class="col-lg-4 col-md-12">
            <label class="form-label text-muted small fw-semibold mb-1">Search</label>
            <div class="input-group media-search-group">
                <span class="input-group-text bg-white border-end-0 pe-1">
                    <i class="bi bi-search" style="font-size:0.85rem; color:#6c757d;"></i>
                </span>
                <input type="text"
                       id="searchMedia"
                       class="form-control border-start-0 border-end-0 ps-1 media-search-input"
                       placeholder="Search by title, keyword..."
                       style="box-shadow:none;"
                       autocomplete="off">
                <button class="btn btn-outline-secondary border-start-0 media-search-clear" type="button"
                        id="clearSearchBtn" title="Clear (Esc)" style="display:none;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        {{-- Date Range --}}
        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-semibold mb-1">Date range</label>
            <select id="dateRangeSelect" class="form-select">
                <option value="">Any time</option>
                <option value="today">Today</option>
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 3 months</option>
            </select>
        </div>

        {{-- Type --}}
        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-semibold mb-1">Type</label>
            <select id="typeSelect" class="form-select">
                <option value="">All files</option>
                <option value="image">Images</option>
            </select>
        </div>

        {{-- Sort By --}}
        <div class="col-lg-2 col-md-6">
            <label class="form-label text-muted small fw-semibold mb-1">Sort by</label>
            <select id="sortSelect" class="form-select">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="name">Name A–Z</option>
            </select>
        </div>

        {{-- Actions --}}
        <div class="col-lg-2 col-md-6 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" onclick="applyFilters()">
                <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>
            <button class="btn btn-outline-secondary" onclick="resetFilters()" title="Reset all filters">
                Reset
            </button>
        </div>
    </div>

    {{-- ── Active Filter Chips ────────────────────────────────────────── --}}
    <div id="activeFilters" class="d-flex flex-wrap gap-2 mb-3" style="min-height:28px;"></div>

    {{-- ── Results Info ───────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span id="resultsInfo" class="text-muted small"></span>
    </div>

    {{-- ── Media Grid ─────────────────────────────────────────────────── --}}
    <div class="row g-3" id="mediaLibraryGrid">
        <div class="col-12 text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>

    {{-- ── Load More ──────────────────────────────────────────────────── --}}
    <div class="mt-4 d-flex justify-content-center d-none" id="mediaPagination">
        <button class="btn btn-outline-primary px-5" id="loadMoreBtn" onclick="loadMore()">
            Load More
        </button>
    </div>

</section>

{{-- Edit Media Modal --}}
<div class="modal fade" id="editMediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white" style="border-bottom: none;">
                <h5 class="modal-title fw-bold">Edit Media</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editMediaForm">
                    <input type="hidden" id="edit_media_id">
                    <div class="text-center mb-3">
                        <img id="edit_media_preview" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Alt Text</label>
                        <input type="text" class="form-control" id="edit_alt_text" name="alt_text">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Keywords</label>
                        <input type="text" class="form-control" id="edit_keywords" name="keywords" placeholder="Comma separated keywords">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Caption</label>
                        <textarea class="form-control" id="edit_caption" name="caption" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="saveMediaBtn" onclick="saveMedia()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let lastPage    = 1;
    let totalFiles  = 0;

    // ── State ──────────────────────────────────────────────────────────────
    const state = {
        search    : '',
        dateRange : '',
        type      : '',
        sort      : 'newest',
    };

    // ── Keyboard shortcut: / to focus search ───────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            document.getElementById('searchMedia').focus();
        }
        if (e.key === 'Escape') {
            const s = document.getElementById('searchMedia');
            if (document.activeElement === s) { s.value = ''; triggerSearch(); s.blur(); }
        }
    });

    // ── Search input live debounce ─────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput  = document.getElementById('searchMedia');
        const clearBtn     = document.getElementById('clearSearchBtn');

        searchInput.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
            clearTimeout(this._t);
            this._t = setTimeout(() => triggerSearch(), 300);
        });

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            triggerSearch();
        });

        loadMediaLibrary(1);

        editModal = new bootstrap.Modal(document.getElementById('editMediaModal'));
    });

    function triggerSearch() {
        state.search = document.getElementById('searchMedia').value.trim();
        renderActiveFilters();
        loadMediaLibrary(1);
    }

    function applyFilters() {
        state.search    = document.getElementById('searchMedia').value.trim();
        state.dateRange = document.getElementById('dateRangeSelect').value;
        state.type      = document.getElementById('typeSelect').value;
        state.sort      = document.getElementById('sortSelect').value;
        renderActiveFilters();
        loadMediaLibrary(1);
    }

    function resetFilters() {
        document.getElementById('searchMedia').value    = '';
        document.getElementById('dateRangeSelect').value = '';
        document.getElementById('typeSelect').value     = '';
        document.getElementById('sortSelect').value     = 'newest';
        document.getElementById('clearSearchBtn').style.display = 'none';
        state.search = state.dateRange = state.type = '';
        state.sort   = 'newest';
        renderActiveFilters();
        loadMediaLibrary(1);
    }

    // ── Active filter chips ────────────────────────────────────────────────
    function renderActiveFilters() {
        const container = document.getElementById('activeFilters');
        const chips = [];

        const dateLabels = { today: 'Today', '7': 'Last 7 days', '30': 'Last 30 days', '90': 'Last 3 months' };
        const typeLabels = { image: 'Images' };

        if (state.search)    chips.push({ label: `Search: ${state.search}`,           key: 'search'    });
        if (state.dateRange) chips.push({ label: dateLabels[state.dateRange] || state.dateRange, key: 'dateRange' });
        if (state.type)      chips.push({ label: typeLabels[state.type]      || state.type,      key: 'type'      });

        if (chips.length === 0) {
            container.innerHTML = '';
            document.getElementById('activeFilters').previousElementSibling && null;
            return;
        }

        container.innerHTML = '<span class="text-muted small me-1 align-self-center">Active filters:</span>' +
            chips.map(c => `
                <span class="badge rounded-pill border text-dark fw-normal px-3 py-2"
                      style="background:#f0fdf4; border-color:#d1fae5 !important; font-size:0.8rem;">
                    ${c.label}
                    <button type="button" class="btn-close btn-close ms-1"
                            style="font-size:0.55rem; vertical-align:middle;"
                            onclick="removeFilter('${c.key}')" aria-label="Remove filter"></button>
                </span>`).join('');
    }

    function removeFilter(key) {
        if (key === 'search')    { state.search = '';    document.getElementById('searchMedia').value    = ''; document.getElementById('clearSearchBtn').style.display='none'; }
        if (key === 'dateRange') { state.dateRange = ''; document.getElementById('dateRangeSelect').value = ''; }
        if (key === 'type')      { state.type = '';      document.getElementById('typeSelect').value     = ''; }
        renderActiveFilters();
        loadMediaLibrary(1);
    }

    // ── Highlight search term in filename ─────────────────────────────────
    function highlight(text, term) {
        if (!term) return text;
        const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="p-0 bg-warning bg-opacity-50">$1</mark>');
    }

    // ── Build date range params ────────────────────────────────────────────
    function getDateParams() {
        const v = state.dateRange;
        if (!v) return '';
        if (v === 'today') {
            const t = new Date().toISOString().split('T')[0];
            return `&start_date=${t}&end_date=${t}`;
        }
        const end   = new Date();
        const start = new Date();
        start.setDate(end.getDate() - parseInt(v));
        return `&start_date=${start.toISOString().split('T')[0]}&end_date=${end.toISOString().split('T')[0]}`;
    }

    // ── Main load function ─────────────────────────────────────────────────
    function loadMediaLibrary(page = 1) {
        currentPage = page;

        if (page === 1) {
            document.getElementById('mediaLibraryGrid').innerHTML =
                '<div class="col-12 text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
            document.getElementById('resultsInfo').textContent = '';
        }

        const typeParam = state.type ? `&type=${encodeURIComponent(state.type)}` : '';
        const params = `page=${page}&search=${encodeURIComponent(state.search)}${getDateParams()}${typeParam}&sort=${state.sort}`;

        fetch(`{{ route('admin.media.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            lastPage   = data.last_page;
            totalFiles = data.total || data.data.length;

            // Results info
            const activeCount = [state.search, state.dateRange, state.type].filter(Boolean).length;
            document.getElementById('resultsInfo').innerHTML =
                `<strong>${totalFiles}</strong> file${totalFiles !== 1 ? 's' : ''} found` +
                (activeCount ? ' &middot; <a href="#" onclick="resetFilters();return false;" class="text-muted">clear filters</a>' : '');

            let html = '';
            if (data.data.length === 0 && page === 1) {
                html = '<div class="col-12 text-center text-muted p-5">No media found.</div>';
            } else {
                data.data.forEach(media => {
                    const url      = `/storage/${media.path}`;
                    const name     = media.title || media.original_filename || '';
                    const nameHtml = highlight(name, state.search);
                    html += `
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6" id="media-card-${media.id}">
                            <div class="card h-100 border-0 shadow-sm overflow-hidden position-relative"
                                 style="border-radius:10px;">
                                <img src="${url}"
                                     class="card-img-top object-fit-cover"
                                     style="height:150px; cursor:pointer;"
                                     alt="${media.alt_text || ''}"
                                     onclick='openEditModal(${JSON.stringify(media)})'>
                                <div class="card-body p-2">
                                    <small class="d-block text-truncate fw-semibold"
                                           style="font-size:0.78rem;"
                                           title="${name}">${nameHtml}</small>
                                </div>
                                <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                                        style="width:28px;height:28px;padding:0;line-height:1;border-radius:6px;"
                                        onclick="deleteMedia(${media.id})">
                                    <i class="bi bi-trash" style="font-size:0.7rem;"></i>
                                </button>
                            </div>
                        </div>`;
                });
            }

            if (page === 1) {
                document.getElementById('mediaLibraryGrid').innerHTML = html;
            } else {
                document.getElementById('mediaLibraryGrid').insertAdjacentHTML('beforeend', html);
            }

            document.getElementById('mediaPagination').classList.toggle('d-none', currentPage >= lastPage);
        })
        .catch(() => {
            document.getElementById('mediaLibraryGrid').innerHTML =
                '<div class="col-12 text-center text-muted p-5">Could not load media. Please try again.</div>';
        });
    }

    function loadMore() {
        if (currentPage < lastPage) loadMediaLibrary(currentPage + 1);
    }

    // ── Edit modal ─────────────────────────────────────────────────────────
    let editModal;

    function openEditModal(media) {
        document.getElementById('edit_media_id').value      = media.id;
        document.getElementById('edit_title').value         = media.title || '';
        document.getElementById('edit_alt_text').value      = media.alt_text || '';
        document.getElementById('edit_keywords').value      = media.keywords || '';
        document.getElementById('edit_caption').value       = media.caption || '';
        document.getElementById('edit_media_preview').src   = `/storage/${media.path}`;
        editModal.show();
    }

    function saveMedia() {
        const id  = document.getElementById('edit_media_id').value;
        const btn = document.getElementById('saveMediaBtn');
        btn.disabled = true;
        btn.innerHTML = 'Saving...';

        fetch(`/admin/media/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                _method   : 'PUT',
                _token    : '{{ csrf_token() }}',
                title     : document.getElementById('edit_title').value,
                alt_text  : document.getElementById('edit_alt_text').value,
                keywords  : document.getElementById('edit_keywords').value,
                caption   : document.getElementById('edit_caption').value,
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = 'Save Changes';
            if (data.success) {
                editModal.hide();
                Swal.fire({ icon: 'success', title: 'Saved', text: 'Media updated successfully!', timer: 1500, showConfirmButton: false });
                loadMediaLibrary(1);
            } else {
                Swal.fire('Error', 'Failed to update media.', 'error');
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = 'Save Changes'; Swal.fire('Error', 'An error occurred.', 'error'); });
    }

    function deleteMedia(id) {
        Swal.fire({
            title: 'Are you sure?', text: 'You want to delete this media file?',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                fd.append('_method', 'DELETE');

                fetch(`/admin/media/${id}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`media-card-${id}`).remove();
                        totalFiles = Math.max(0, totalFiles - 1);
                        document.getElementById('resultsInfo').innerHTML =
                            `<strong>${totalFiles}</strong> file${totalFiles !== 1 ? 's' : ''} found`;
                        Swal.fire('Deleted!', 'Media has been deleted.', 'success');
                    } else {
                        Swal.fire('Error', 'Could not delete media.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'An error occurred.', 'error'));
            }
        });
    }
</script>
@endpush
