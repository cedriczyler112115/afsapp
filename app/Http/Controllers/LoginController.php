<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        [$userLevels, $divisions, $registrationLoadError] = $this->loadRegistrationLookups();

        return view('login', [
            'showRegister' => false,
            'userLevels' => $userLevels,
            'divisions' => $divisions,
            'registrationLoadError' => $registrationLoadError,
        ]);
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        [$userLevels, $divisions, $registrationLoadError] = $this->loadRegistrationLookups();

        return view('login', [
            'showRegister' => true,
            'userLevels' => $userLevels,
            'divisions' => $divisions,
            'registrationLoadError' => $registrationLoadError,
        ]);
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'login')
                ->onlyInput('email');
        }

        $credentials = $validator->validated();
        $remember = $request->has('remember');

        $loginCredentials = $credentials;
        if (Schema::hasColumn('users', 'is_status')) {
            $loginCredentials['is_status'] = 1;
        }

        if (Auth::attempt($loginCredentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        if (Schema::hasColumn('users', 'is_status')) {
            $user = User::where('email', $credentials['email'])->first();
            if ($user && (int) $user->is_status === 0 && Hash::check($credentials['password'], (string) $user->password)) {
                return back()
                    ->withErrors(['email' => 'Your account is pending approval. Please wait for activation.'], 'login')
                    ->onlyInput('email');
            }
        }

        return back()
            ->withErrors(['email' => 'The provided credentials do not match our records.'], 'login')
            ->onlyInput('email');
    }

    public function register(Request $request)
    {
        $request->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name', ''))),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        if (! $request->filled('level_id') && $request->filled('u_level')) {
            $request->merge(['level_id' => $request->input('u_level')]);
        }

        $sectionId = (int) $request->input('section_id');

        $emailUniqueRule = Rule::unique('users', 'email');
        if (Schema::hasColumn('users', 'is_status')) {
            $emailUniqueRule = $emailUniqueRule->where(function ($q) {
                $q->where('is_status', 1);
            });
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'min:5',
                'max:150',
                'regex:/^[\pL][\pL\s.\'-]{1,74},\s*[\pL][\pL\s.\'-]{1,74}$/u',
            ],
            'email' => ['required', 'email', 'max:255', $emailUniqueRule],
            'level_id' => ['required', 'integer', Rule::exists('user_level', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('lib_division', 'id')],
            'section_id' => [
                'required',
                'integer',
                Rule::exists('lib_section', 'id')->where(function ($q) use ($request) {
                    $q->where('division_id', $request->input('division_id'));
                }),
            ],
            'province_code' => [
                Rule::requiredIf(in_array($sectionId, [59, 61], true)),
                'nullable',
                'integer',
                Rule::exists('lib_provinces', 'prov_code')->where(function ($q) {
                    $q->where('region_code', 16);
                }),
            ],
            'municipality_code' => [
                Rule::requiredIf($sectionId === 61),
                'nullable',
                'integer',
                Rule::exists('lib_cities', 'city_code')->where(function ($q) use ($request) {
                    $q->where('prov_code', $request->input('province_code'));
                }),
            ],
            'cluster' => [
                Rule::requiredIf($sectionId === 60),
                'nullable',
                'integer',
                Rule::in([1, 2]),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
            'terms' => ['accepted'],
        ], [
            'name.regex' => 'Name format must be: lastname, firstname middlename.',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            'terms.accepted' => 'You must agree to the Terms and Policy.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('register')
                ->withErrors($validator, 'register')
                ->withInput();
        }

        try {
            $data = $validator->validated();

            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'level_id' => $data['level_id'],
                'division_id' => $data['division_id'],
                'section_id' => $data['section_id'],
                'province' => $data['province_code'] ?? null,
                'cluster' => $data['cluster'] ?? null,
                'municipality' => $data['municipality_code'] ?? null,
                'is_status' => 0,
                'password' => Hash::make($data['password']),
            ]);
        } catch (QueryException|\PDOException $e) {
            return redirect()
                ->route('register')
                ->withErrors(['database' => 'Registration failed due to a database error. Please try again later.'], 'register')
                ->withInput();
        }

        return redirect()
            ->route('login')
            ->with('status', 'Account created successfully. You can now log in.');
    }

    public function sectionsByDivision(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid division.',
                'sections' => [],
            ], 422);
        }

        $divisionId = (int) $request->input('division_id');

        try {
            $sections = DB::table('lib_section')
                ->select([DB::raw('id as section_id'), 'section_name'])
                ->where('division_id', $divisionId)
                ->whereNotNull('id')
                ->orderBy('section_name')
                ->get();
        } catch (QueryException|\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load sections due to a database error.',
                'sections' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'sections' => $sections,
        ]);
    }

    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email.',
                'exists' => false,
            ], 422);
        }

        $email = mb_strtolower(trim((string) $validator->validated()['email']));

        try {
            $query = DB::table('users')->where('email', $email);
            if (Schema::hasColumn('users', 'is_status')) {
                $query->where('is_status', 1);
            }

            return response()->json([
                'success' => true,
                'exists' => $query->exists(),
            ]);
        } catch (QueryException|\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to check email due to a database error.',
                'exists' => false,
            ], 500);
        }
    }

    public function provincesByRegion(Request $request)
    {
        try {
            $provinces = DB::table('lib_provinces')
                ->select(['prov_code', 'prov_name'])
                ->where('region_code', 16)
                ->orderBy('prov_name')
                ->get();
        } catch (QueryException|\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load provinces due to a database error.',
                'provinces' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'provinces' => $provinces,
        ]);
    }

    public function citiesByProvince(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prov_code' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid province.',
                'cities' => [],
            ], 422);
        }

        $provCode = (int) $request->input('prov_code');

        try {
            $cities = DB::table('lib_cities')
                ->select(['city_code', 'city_name'])
                ->where('prov_code', $provCode)
                ->orderBy('city_name')
                ->get();
        } catch (QueryException|\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load municipalities due to a database error.',
                'cities' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }

    private function loadRegistrationLookups(): array
    {
        $operation = 'registration.lookups';

        try {
            $this->retryDb(function () {
                DB::connection()->getPdo();
            }, $operation.'.connect');

            if (! $this->requiredRegistrationTablesExist()) {
                Log::error('registration_lookups_tables_missing', [
                    'operation' => $operation,
                    'db_config' => $this->safeDbConfigSummary(),
                ]);

                return [collect(), collect(), 'Registration is temporarily unavailable due to a database schema configuration issue.'];
            }

            $userLevels = $this->retryDb(function () {
                return DB::table('user_level')
                    ->select([DB::raw('id as level_id'), 'level_name'])
                    ->whereNotNull('id')
                    ->where(function ($q) {
                        $q->whereNull('is_status')->orWhere('is_status', 1);
                    })
                    ->orderBy('id')
                    ->get();
            }, $operation.'.user_levels');

            $divisions = $this->retryDb(function () {
                return DB::table('lib_division')
                    ->select([DB::raw('id as division_id'), 'division_name'])
                    ->whereNotNull('id')
                    ->whereNotNull('division_name')
                    ->where(function ($q) {
                        $q->whereNull('is_status')->orWhere('is_status', 1);
                    })
                    ->orderBy('division_name')
                    ->get();
            }, $operation.'.divisions');

            return [$userLevels, $divisions, null];
        } catch (QueryException|\PDOException $e) {
            $payload = [
                'operation' => $operation,
                'exception' => get_class($e),
                'sqlstate' => $this->dbSqlState($e),
                'driver_code' => $this->dbDriverCode($e),
                'db_config' => $this->safeDbConfigSummary(),
            ];
            Log::error('registration_lookups_failed', $payload);

            return [collect(), collect(), $this->dbUserMessage($e)];
        }
    }

    private function retryDb(callable $callback, string $operation, int $maxAttempts = 3, int $sleepMs = 150)
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return $callback();
            } catch (QueryException|\PDOException $e) {
                if (! $this->isTransientDbException($e) || $attempt >= $maxAttempts) {
                    Log::error('db_operation_failed', [
                        'operation' => $operation,
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'sqlstate' => $this->dbSqlState($e),
                        'driver_code' => $this->dbDriverCode($e),
                        'db_config' => $this->safeDbConfigSummary(),
                        'exception' => get_class($e),
                    ]);
                    throw $e;
                }

                Log::warning('db_retry_attempt', [
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'sqlstate' => $this->dbSqlState($e),
                    'driver_code' => $this->dbDriverCode($e),
                    'db_config' => $this->safeDbConfigSummary(),
                ]);

                try {
                    DB::purge(DB::getDefaultConnection());
                    DB::reconnect(DB::getDefaultConnection());
                } catch (\Throwable $t) {
                    Log::warning('db_reconnect_failed', [
                        'operation' => $operation,
                        'connection' => DB::getDefaultConnection(),
                        'exception' => get_class($t),
                    ]);
                }

                usleep($sleepMs * 1000);
            }
        }
    }

    private function isTransientDbException(QueryException|\PDOException $e): bool
    {
        $sqlState = $this->dbSqlState($e);
        $driverCode = $this->dbDriverCode($e);
        $message = (string) $e->getMessage();

        if (in_array($sqlState, ['42S02', '42S22'], true)) {
            return false;
        }

        if (in_array($driverCode, [1044, 1045, 1049], true)) {
            return false;
        }

        if (in_array($driverCode, [2002, 2006, 2013], true)) {
            return true;
        }

        if (str_contains($message, '[2002]') || str_contains($message, '[2006]') || str_contains($message, '[2013]')) {
            return true;
        }

        return false;
    }

    private function dbUserMessage(QueryException|\PDOException $e): string
    {
        $sqlState = $this->dbSqlState($e);
        $driverCode = $this->dbDriverCode($e);

        if ($driverCode === 1049) {
            return 'Registration is temporarily unavailable because the configured database was not found.';
        }

        if (in_array($sqlState, ['42S02', '42S22'], true)) {
            return 'Registration is temporarily unavailable due to a database schema configuration issue.';
        }

        if (in_array($driverCode, [1044, 1045], true)) {
            return 'Registration is temporarily unavailable due to a database authentication issue.';
        }

        if (in_array($driverCode, [2002, 2006, 2013], true)) {
            return 'Registration is temporarily unavailable because the database is unreachable. Please try again later.';
        }

        return 'Registration is temporarily unavailable due to a database error. Please try again later.';
    }

    private function dbSqlState(QueryException|\PDOException $e): ?string
    {
        if ($e instanceof QueryException) {
            $info = $e->errorInfo;
            if (is_array($info) && isset($info[0]) && is_string($info[0])) {
                return $info[0];
            }
        }

        return null;
    }

    private function dbDriverCode(QueryException|\PDOException $e): ?int
    {
        if ($e instanceof QueryException) {
            $info = $e->errorInfo;
            if (is_array($info) && isset($info[1]) && is_numeric($info[1])) {
                return (int) $info[1];
            }
        }
        $code = $e->getCode();

        return is_numeric($code) ? (int) $code : null;
    }

    private function safeDbConfigSummary(): array
    {
        $name = DB::getDefaultConnection();
        $config = config('database.connections.'.$name, []);

        return [
            'name' => $name,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'database' => $config['database'] ?? null,
        ];
    }

    private function requiredRegistrationTablesExist(): bool
    {
        try {
            return Schema::hasTable('user_level')
                && Schema::hasTable('lib_division')
                && Schema::hasTable('lib_section');
        } catch (\Throwable $t) {
            Log::warning('registration_tables_check_failed', [
                'connection' => DB::getDefaultConnection(),
                'db_config' => $this->safeDbConfigSummary(),
                'exception' => get_class($t),
            ]);

            return false;
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
