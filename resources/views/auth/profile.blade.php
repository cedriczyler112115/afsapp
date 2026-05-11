@extends('layouts.app')

@section('title', 'My Profile - 4Ps AFS-IS')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-person me-2"></i>My Profile</h1>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('password_status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('password_status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Profile Data Card -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold text-primary mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required placeholder="Lastname, Firstname Middlename">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled readonly>
                                <div class="form-text">Your email address cannot be changed.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">User Level</label>
                                <select name="level_id" class="form-select" required>
                                    <option value="">Select Level</option>
                                    @foreach($userLevels as $lvl)
                                        <option value="{{ $lvl->level_id }}" {{ old('level_id', $user->level_id) == $lvl->level_id ? 'selected' : '' }}>
                                            {{ $lvl->level_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Division</label>
                                <select name="division_id" id="division_id" class="form-select" required>
                                    <option value="">Select Division</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->division_id }}" {{ old('division_id', $user->division_id) == $div->division_id ? 'selected' : '' }}>
                                            {{ $div->division_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Section</label>
                                <select name="section_id" id="section_id" class="form-select" required>
                                    <option value="">Select Section</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec->section_id }}" {{ old('section_id', $user->section_id) == $sec->section_id ? 'selected' : '' }}>
                                            {{ $sec->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="dynamicFields" style="display: none; width: 100%;">
                                <div class="row g-3">
                                    <div class="col-md-6" id="fieldProvince" style="display: none;">
                                        <label class="form-label small fw-semibold">Province</label>
                                        <select name="province_code" id="province_code" class="form-select">
                                            <option value="">Select Province</option>
                                            @foreach($provinces as $prov)
                                                <option value="{{ $prov->prov_code }}" {{ old('province_code', $user->province) == $prov->prov_code ? 'selected' : '' }}>
                                                    {{ $prov->prov_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="fieldCity" style="display: none;">
                                        <label class="form-label small fw-semibold">City/Municipality</label>
                                        <select name="municipality_code" id="city_code" class="form-select">
                                            <option value="">Select City/Municipality</option>
                                            @foreach($cities as $city)
                                                <option value="{{ $city->city_code }}" {{ old('municipality_code', $user->municipality) == $city->city_code ? 'selected' : '' }}>
                                                    {{ $city->city_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="fieldCluster" style="display: none;">
                                        <label class="form-label small fw-semibold">Cluster</label>
                                        <select name="cluster" id="cluster_id" class="form-select">
                                            <option value="">Select Cluster</option>
                                            <option value="1" {{ old('cluster', $user->cluster) == 1 ? 'selected' : '' }}>Cluster 1</option>
                                            <option value="2" {{ old('cluster', $user->cluster) == 2 ? 'selected' : '' }}>Cluster 2</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Profile Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Data Card -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold text-primary mb-0">Security</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        
                        @if(!empty(Auth::user()->password))
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                            </div>
                        @else
                            <div class="alert alert-info py-2 small">
                                <i class="bi bi-info-circle me-1"></i> You currently log in via Google. Setting a password allows you to log in with email and password as well.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">New Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter new password">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required placeholder="Confirm new password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-shield-lock me-2"></i>Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const divisionSelect = document.getElementById('division_id');
    const sectionSelect = document.getElementById('section_id');

    const dynamicFieldsContainer = document.getElementById('dynamicFields');
    const fieldProvince = document.getElementById('fieldProvince');
    const fieldCity = document.getElementById('fieldCity');
    const fieldCluster = document.getElementById('fieldCluster');
    
    const provinceSelect = document.getElementById('province_code');
    const citySelect = document.getElementById('city_code');
    const clusterSelect = document.getElementById('cluster_id');

    function toggleDynamicFields(val) {
        if (!val) {
            dynamicFieldsContainer.style.display = 'none';
            return;
        }

        const id = parseInt(val, 10);
        let showDynamic = false;

        // RPMO (59) -> needs Province
        // M&E (60) -> needs Cluster
        // C/MAT (61) -> needs Province & City/Municipality
        if (id === 59) {
            showDynamic = true;
            fieldProvince.style.display = 'block';
            fieldCity.style.display = 'none';
            fieldCluster.style.display = 'none';
        } else if (id === 60) {
            showDynamic = true;
            fieldProvince.style.display = 'none';
            fieldCity.style.display = 'none';
            fieldCluster.style.display = 'block';
        } else if (id === 61) {
            showDynamic = true;
            fieldProvince.style.display = 'block';
            fieldCity.style.display = 'block';
            fieldCluster.style.display = 'none';
        } else {
            fieldProvince.style.display = 'none';
            fieldCity.style.display = 'none';
            fieldCluster.style.display = 'none';
        }

        dynamicFieldsContainer.style.display = showDynamic ? 'block' : 'none';
    }

    divisionSelect.addEventListener('change', function() {
        const divisionId = this.value;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';

        if (divisionId) {
            const formData = new FormData();
            formData.append('division_id', divisionId);

            fetch('{{ route("register.sections") }}', { // Logged-in users can access this now!
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Select Section</option>';
                if (data.success && data.sections) {
                    data.sections.forEach(sec => {
                        options += `<option value="${sec.section_id}">${sec.section_name}</option>`;
                    });
                }
                sectionSelect.innerHTML = options;
                toggleDynamicFields(sectionSelect.value);
            }).catch(() => {
                sectionSelect.innerHTML = '<option value="">Error Loading</option>';
            });
        } else {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            toggleDynamicFields('');
        }
    });

    sectionSelect.addEventListener('change', function() {
        toggleDynamicFields(this.value);
    });

    provinceSelect.addEventListener('change', function() {
        const provCode = this.value;
        if (provCode) {
            const formData = new FormData();
            formData.append('prov_code', provCode);

            fetch('{{ route("register.cities") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Select City/Municipality</option>';
                if (data.success && data.cities) {
                    data.cities.forEach(city => {
                        options += `<option value="${city.city_code}">${city.city_name}</option>`;
                    });
                }
                citySelect.innerHTML = options;
            });
        } else {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        }
    });

    // Initial Trigger
    toggleDynamicFields(sectionSelect.value);
});
</script>
@endpush
