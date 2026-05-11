@extends('layouts.app')

@section('title', 'Sidebar Access Management - 4Ps AFS-IS')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-shield-lock me-2"></i>Sidebar Access Matrix</h1>
        <button form="accessForm" type="submit" class="btn btn-primary" id="saveBtn" disabled>
            <i class="bi bi-save me-1"></i> Save Access Rights
        </button>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Configuration Context Section -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold mb-0 text-dark">Grant Access By:</h5>
                </div>
                <div class="card-body">
                    <form id="accessContextForm">
                        <div class="mb-3">
                            <select id="accessType" class="form-select form-select-lg">
                                <option value="" selected disabled>Select Access Type...</option>
                                <option value="level">User Level (Role)</option>
                                <option value="user">Specific User</option>
                            </select>
                        </div>
                        
                        <div id="levelSelector" class="mb-3 d-none">
                            <label class="form-label text-secondary fw-semibold">Select Target Level</label>
                            <select id="levelId" class="form-select">
                                <option value="" selected disabled>Select a Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->level_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="userSelector" class="mb-3 d-none">
                            <label class="form-label text-secondary fw-semibold">Select Target User</label>
                            <select id="userId" class="form-select">
                                <option value="" selected disabled>Select a User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    
                    <div id="loadingIndicator" class="text-center d-none mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 small">Loading access rights...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Section -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 rounded-3" style="min-height: 400px;">
                <div class="card-body p-0 border-0">
                    <div id="modulesContainer" class="p-4" style="opacity: 0.5; pointer-events: none;">
                        <h5 class="fw-bold text-dark mb-3" id="modulesTitle">Available Sidebar Links</h5>
                        <p class="text-muted small mb-4">Please select an access type and target first.</p>
                        
                        <form id="accessForm" action="{{ route('access.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="access_type" id="formAccessType" value="">
                            <input type="hidden" name="access_target_id" id="formTargetId" value="">
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 250px;" class="ps-3">Module Name / Link</th>
                                            <th class="text-center" width="100">Grant</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modulesList">
                                        @foreach($modules as $key => $label)
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="fw-semibold text-secondary">{{ $label }}</div>
                                                    <div class="small text-muted" style="font-size: 0.75rem;">Key: <code>{{ $key }}</code></div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center m-0">
                                                        <input class="form-check-input module-checkbox" 
                                                               type="checkbox" 
                                                               name="access[]" 
                                                               value="{{ $key }}" 
                                                               id="access_{{ Str::slug($key) }}"
                                                               style="width: 1.25em; height: 1.25em;">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('accessType');
        const levelSelector = document.getElementById('levelSelector');
        const userSelector = document.getElementById('userSelector');
        
        const levelIdSelect = document.getElementById('levelId');
        const userIdSelect = document.getElementById('userId');
        
        const loadingIndicator = document.getElementById('loadingIndicator');
        const modulesContainer = document.getElementById('modulesContainer');
        const modulesTitle = document.getElementById('modulesTitle');
        const saveBtn = document.getElementById('saveBtn');
        const checkboxes = document.querySelectorAll('.module-checkbox');
        
        const formAccessType = document.getElementById('formAccessType');
        const formTargetId = document.getElementById('formTargetId');
        
        const configUrl = '{{ route("access.config") }}';

        // Initialize state
        checkboxes.forEach(cb => cb.checked = false);

        typeSelect.addEventListener('change', function() {
            const type = this.value;
            levelIdSelect.value = '';
            userIdSelect.value = '';
            
            if(type === 'level') {
                levelSelector.classList.remove('d-none');
                userSelector.classList.add('d-none');
            } else if(type === 'user') {
                levelSelector.classList.add('d-none');
                userSelector.classList.remove('d-none');
            }
            disableModules();
        });

        levelIdSelect.addEventListener('change', function() {
            if(this.value) loadConfig('level', this.value, this.options[this.selectedIndex].text);
        });

        userIdSelect.addEventListener('change', function() {
            if(this.value) loadConfig('user', this.value, this.options[this.selectedIndex].text);
        });

        function loadConfig(type, id, displayName) {
            loadingIndicator.classList.remove('d-none');
            disableModules();
            
            fetch(`${configUrl}?type=${type}&id=${id}`)
                .then(response => {
                    if(!response.ok) throw new Error("Network response error");
                    return response.json();
                })
                .then(data => {
                    formAccessType.value = type;
                    formTargetId.value = id;
                    modulesTitle.innerHTML = `Configure Access for: <span class="text-primary">${displayName}</span>`;
                    
                    const accessRights = data.access_rights || [];
                    
                    checkboxes.forEach(cb => {
                        cb.checked = accessRights.includes(cb.value);
                    });
                    
                    enableModules();
                })
                .catch(error => {
                    console.error('Error fetching access rights:', error);
                    alert('Error fetching access rights. Please try again.');
                })
                .finally(() => {
                    loadingIndicator.classList.add('d-none');
                });
        }
        
        function disableModules() {
            modulesContainer.style.opacity = '0.5';
            modulesContainer.style.pointerEvents = 'none';
            saveBtn.disabled = true;
            formAccessType.value = '';
            formTargetId.value = '';
            checkboxes.forEach(cb => cb.checked = false);
            modulesTitle.textContent = 'Available Sidebar Links';
        }
        
        function enableModules() {
            modulesContainer.style.opacity = '1';
            modulesContainer.style.pointerEvents = 'auto';
            saveBtn.disabled = false;
        }
    });
</script>
@endpush
@endsection
