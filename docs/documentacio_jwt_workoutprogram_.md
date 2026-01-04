# Edzésprogram REST API megvalósítása Laravel környezetben - JWT Authentikáció

**base_url:** `http://127.0.0.1:8000/api`

Az API-t olyan funkciókkal kell ellátni, amelyek lehetővé teszik annak nyilvános elérhetőségét.   Ennek a backendnek a fő célja, hogy kiszolgálja a frontendet, amelyet a felhasználók edzésprogramokra való feliratkozásra és az edzéseik nyomon követésére használnak.

**Funkciók:**
- Authentikáció (login, JWT token kezelés) - jelszó nélküli bejelentkezés email alapján
- Felhasználó beiratkozhat egy edzésprogramra
- Edzésprogram teljesítési státuszának (progress) követése
- **Admin felhasználók kezelhetik a többi felhasználót**
- **Admin felhasználók CRUD műveleteket végezhetnek az edzésprogramokon**
- A teszteléshez készíts:   
  - 1 admin felhasználót (admin@example.com)
  - 5 student felhasználót (különböző korosztályok)
  - 3 releváns edzésprogramot (különböző nehézségi szintekkel)
  - Néhány beiratkozást különböző progress értékekkel

Az adatbázis neve: `workout_program_jwt`

## Végpontok:  
A `Content-Type` és az `Accept` headerkulcsok mindig `application/json` formátumúak legyenek.  

**JWT Token használata:**
Védett végpontoknál a HTTP headerben így kell megadni:
```
Authorization: Bearer <JWT_TOKEN>
```

Érvénytelen vagy hiányzó token esetén a backendnek `401 Unauthorized` választ kell visszaadnia:   
```json
Response:  401 Unauthorized
{
  "message": "Unauthenticated."
}
```

### Nem védett végpontok:
- **GET** `/ping` - teszteléshez
- **POST** `/register` - regisztrációhoz
- **POST** `/login` - belépéshez (jelszó nélkül, csak email)

### Védett végpontok (JWT token szükséges):
- **POST** `/logout` - kijelentkezés
- **GET** `/users/me` - saját profil
- **PUT** `/users/me` - saját profil módosítása
- **GET** `/users` - felhasználók listázása (mindenki)
- **GET** `/users/: id` - felhasználó megtekintése
- **DELETE** `/users/:id` - felhasználó törlése (**csak admin**)
- **GET** `/workouts` - edzésprogramok listázása
- **GET** `/workouts/:id` - edzésprogram megtekintése
- **POST** `/workouts` - edzésprogram létrehozása (**csak admin**)
- **PUT** `/workouts/:id` - edzésprogram módosítása (**csak admin**)
- **DELETE** `/workouts/:id` - edzésprogram törlése (**csak admin**)
- **POST** `/workouts/:id/enroll` - beiratkozás
- **POST** `/workouts/:id/complete` - teljesítés

### Hibák:   
- **400 Bad Request:** A kérés hibás formátumú.   Ezt a hibát akkor kell visszaadni, ha a kérés hibásan van formázva, vagy ha hiányoznak a szükséges mezők.  
- **401 Unauthorized:** A felhasználó nem jogosult a kérés végrehajtására.  Ezt a hibát akkor kell visszaadni, ha érvénytelen a JWT token.   
- **403 Forbidden:** A felhasználó nem jogosult a kérés végrehajtására. Ezt a hibát akkor kell visszaadni, ha a felhasználó nem admin, vagy nincs beiratkozva az edzésprogramra.  
- **404 Not Found:** A kért erőforrás nem található. Ezt a hibát akkor kell visszaadni, ha a kért edzésprogram vagy felhasználó nem található.  
- **422 Unprocessable Entity:** Validációs hiba. Ezt a hibát akkor kell visszaadni, ha a kérés adatai nem felelnek meg a validációs szabályoknak.  

---

## Felhasználókezelés

**POST** `/register`

Új felhasználó regisztrálása.  Jelszó megadása nem szükséges.  Az email címnek egyedinek kell lennie.  Alapértelmezett role: `student`.

Request:  
```json
{
  "name": "Nagy Péter",
  "email": "peter@example.com",
  "age": 25
}
```

Response: `201 Created`
```json
{
  "message": "User created successfully",
  "user": {
    "id": 7,
    "name": "Nagy Péter",
    "email": "peter@example.com",
    "age": 25,
    "role": "student"
  }
}
```

Validációs hiba esetén:  `422 Unprocessable Entity`
```json
{
  "message": "Failed to register user",
  "errors": {
    "email": ["The email has already been taken. "]
  }
}
```

---

**POST** `/login`

Bejelentkezés csak e-mail címmel (jelszó nélkül). **JWT token generálás.**

Request:
```json
{
  "email": "admin@example.com"
}
```

Response (sikeres bejelentkezés): `200 OK`
```json
{
  "message": "Login successful",
  "user": {
    "id":  1,
    "name": "Admin",
    "email": "admin@example.com",
    "age": 30,
    "role": "admin"
  },
  "access":  {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

Response (sikertelen bejelentkezés): `401 Unauthorized`
```json
{
  "message": "Invalid email"
}
```

---

> Az innen következő végpontok autentikáltak, tehát a kérés headerében meg kell adni a JWT tokent is

> Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...

**POST** `/logout`

A jelenlegi autentikált felhasználó kijelentkeztetése, a JWT token érvénytelenítése.  

Response (sikeres kijelentkezés): `200 OK`
```json
{
  "message": "Logout successful"
}
```

---

**GET** `/users/me`

Saját felhasználói profil és edzésstatisztikák lekérése.

Response: `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com"
  },
  "stats": {
    "enrolledCourses": 2,
    "completedCourses": 1
  }
}
```

---

**PUT** `/users/me`

Saját felhasználói adatok frissítése.  Az aktuális felhasználó módosíthatja a nevét és/vagy e-mail címét.

Request:
```json
{
  "name": "Admin Új Név",
  "email": "newemail@example.com"
}
```

Response (sikeres frissítés): `200 OK`
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "Admin Új Név",
    "email": "newemail@example.com"
  }
}
```

*Hibák: *
- `422 Unprocessable Entity` – érvénytelen vagy hiányzó mezők, vagy az e-mail már foglalt
- `401 Unauthorized` – ha a JWT token érvénytelen vagy hiányzik

---

**GET** `/users`

Az összes felhasználó listájának lekérése.  **Bárki láthatja, aki be van jelentkezve.**

Response: `200 OK`
```json
{
  "users": [
    {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com"
    },
    {
      "id": 2,
      "name": "Nagy Anna",
      "email": "anna@example.com"
    }
  ]
}
```

---

**GET** `/users/:id`

Egy felhasználó profiljának és statisztikáinak lekérése.

Response: `200 OK`
```json
{
  "user": {
    "id": 2,
    "name": "Nagy Anna",
    "email": "anna@example.com"
  },
  "stats":  {
    "enrolledCourses": 2,
    "completedCourses": 1
  }
}
```

Ha törölt (soft deleted) felhasználót próbáltunk megnézni:   

Response: `404 Not Found`
```json
{
  "message": "User is deleted"
}
```

Ha nem létező felhasználót próbáltunk megnézni:  

Response: `404 Not Found`
```json
{
  "message": "User not found"
}
```

---

**DELETE** `/users/:id` **CSAK ADMIN**

Egy felhasználó törlése (Soft Delete). **Csak admin jogosultsággal.**

Response (sikeres törlés): `200 OK`
```json
{
  "message": "User deleted successfully"
}
```

Response (ha nem admin): `403 Forbidden`
```json
{
  "message": "Unauthorized. Admin access required."
}
```

Response (ha a felhasználó nem található): `404 Not Found`
```json
{
  "message": "User not found"
}
```

---

## Edzésprogram kezelés

**GET** `/workouts`

Az összes elérhető edzésprogram listájának lekérése.

Response: `200 OK`
```json
{
  "workouts":  [
    {
      "id": 1,
      "title": "Kezdő Full Body",
      "description": "Teljes test edzés kezdőknek.  3x hetente ajánlott.",
      "difficulty": "easy"
    },
    {
      "id": 2,
      "title": "Haladó erősítő",
      "description": "Intenzív erősítő edzés haladóknak.  Súlyzós gyakorlatok.",
      "difficulty":  "hard"
    }
  ]
}
```

---

**GET** `/workouts/:id`

Információk lekérése egy adott edzésprogramról és a hozzá csatlakozott felhasználókról.

Response: `200 OK`
```json
{
  "workout": {
    "title": "Kezdő Full Body",
    "description": "Teljes test edzés kezdőknek. 3x hetente ajánlott.",
    "difficulty": "easy"
  },
  "students": [
    {
      "name": "Nagy Anna",
      "email": "anna@example.com",
      "progress": 75,
      "last_done": "2026-01-02"
    }
  ]
}
```

Response (ha nem található): `404 Not Found`

---

**POST** `/workouts` **CSAK ADMIN**

Új edzésprogram létrehozása.  **Csak admin jogosultsággal.**

Request:
```json
{
  "title": "Kardió alapok",
  "description": "Alapvető kardió edzés kezdőknek",
  "difficulty": "easy"
}
```

Response (sikeres létrehozás): `201 Created`
```json
{
  "message": "Workout created successfully",
  "workout": {
    "id": 4,
    "title": "Kardió alapok",
    "description": "Alapvető kardió edzés kezdőknek",
    "difficulty": "easy"
  }
}
```

Response (ha nem admin): `403 Forbidden`
```json
{
  "message": "Unauthorized. Admin access required."
}
```

---

**PUT** `/workouts/:id` **CSAK ADMIN**

Edzésprogram módosítása. **Csak admin jogosultsággal.**

Request:
```json
{
  "title": "Kardió haladó",
  "description":  "Haladó kardió edzés",
  "difficulty": "medium"
}
```

Response (sikeres módosítás): `200 OK`
```json
{
  "message": "Workout updated successfully",
  "workout": {
    "id": 4,
    "title": "Kardió haladó",
    "description": "Haladó kardió edzés",
    "difficulty": "medium"
  }
}
```

Response (ha nem admin): `403 Forbidden`
```json
{
  "message": "Unauthorized. Admin access required."
}
```

---

**DELETE** `/workouts/:id` **CSAK ADMIN**

Edzésprogram törlése. **Csak admin jogosultsággal.**

Response (sikeres törlés): `200 OK`
```json
{
  "message": "Workout deleted successfully"
}
```

Response (ha nem admin): `403 Forbidden`
```json
{
  "message": "Unauthorized. Admin access required."
}
```

---

**POST** `/workouts/:id/enroll`

A jelenlegi felhasználó beiratkozása egy edzésprogramra.  

Response (sikeres beiratkozás): `201 Created`
```json
{
  "message": "Enrolled successfully"
}
```

Response (ha már beiratkozott): `422 Unprocessable Entity`
```json
{
  "message": "Already enrolled"
}
```

Response (ha az edzésprogram nem található): `404 Not Found`

---

**POST** `/workouts/:id/complete`

A jelenlegi felhasználó edzésprogramjának teljesítése.  Ez a progress-t 100%-ra állítja és kitölti a `completed_at` mezőt.

Response (sikeres teljesítés): `200 OK`
```json
{
  "message": "Workout marked as completed"
}
```

Response (ha nincs beiratkozva): `404 Not Found`
```json
{
  "message": "Not enrolled"
}
```

---

## Összefoglalva

| HTTP metódus | Útvonal                    | Jogosultság       | Státuszkódok                                          | Rövid leírás                                      |
|--------------|----------------------------|-------------------|-------------------------------------------------------|---------------------------------------------------|
| GET          | /ping                      | Nyilvános         | 200 OK                                                | API teszteléshez                                  |
| POST         | /register                  | Nyilvános         | 201 Created, 422 Unprocessable Entity                 | Új felhasználó regisztrációja                     |
| POST         | /login                     | Nyilvános         | 200 OK, 401 Unauthorized                              | Bejelentkezés e-maillel (JWT token generálás)    |
| POST         | /logout                    | JWT Hitelesített  | 200 OK, 401 Unauthorized                              | Kijelentkezés (JWT invalidálás)                   |
| GET          | /users/me                  | JWT Hitelesített  | 200 OK, 401 Unauthorized                              | Saját profil és statisztikák lekérése             |
| PUT          | /users/me                  | JWT Hitelesített  | 200 OK, 422 Unprocessable Entity, 401 Unauthorized    | Saját profil adatainak módosítása                 |
| GET          | /users                     | JWT Hitelesített  | 200 OK, 401 Unauthorized                              | Összes felhasználó listázása                      |
| GET          | /users/:id                 | JWT Hitelesített  | 200 OK, 404 Not Found, 401 Unauthorized               | Bármely felhasználó profiljának lekérése          |
| DELETE       | /users/:id                 | **JWT + Admin**   | 200 OK, 403 Forbidden, 404 Not Found, 401 Unauthorized| Felhasználó törlése (Soft Delete)                 |
| GET          | /workouts                  | JWT Hitelesített  | 200 OK, 401 Unauthorized                              | Edzésprogramok listázása                          |
| GET          | /workouts/:id              | JWT Hitelesített  | 200 OK, 404 Not Found, 401 Unauthorized               | Egy edzésprogram részletei                        |
| POST         | /workouts                  | **JWT + Admin**   | 201 Created, 403 Forbidden, 422 Unprocessable Entity  | Edzésprogram létrehozása                          |
| PUT          | /workouts/:id              | **JWT + Admin**   | 200 OK, 403 Forbidden, 404 Not Found                  | Edzésprogram módosítása                           |
| DELETE       | /workouts/:id              | **JWT + Admin**   | 200 OK, 403 Forbidden, 404 Not Found                  | Edzésprogram törlése                              |
| POST         | /workouts/:id/enroll       | JWT Hitelesített  | 201 Created, 422 Unprocessable Entity, 404 Not Found  | Beiratkozás edzésprogramra                        |
| POST         | /workouts/:id/complete     | JWT Hitelesített  | 200 OK, 404 Not Found, 401 Unauthorized               | Edzésprogram teljesítése (100% + completed_at)    |

---

## Adatbázis terv:   

```
+---------------------+       +------------------+        +-------------+
|        users        |       |  user_workouts   |        |  workouts   |
+---------------------+       +------------------+        +-------------+
| id (PK)             |1__    | id (PK)          |     __1| id (PK)     |
| name                |   \__N| user_id (FK)     |    /   | title       |
| email (unique)      |       | workout_id (FK)  |M__/    | description |
| role (student/admin)|       | progress         |        | difficulty  |
| age                 |       | last_done        |        | created_at  |
| deleted_at          |       | completed_at     |        | updated_at  |
| created_at          |       | created_at       |        +-------------+
| updated_at          |       | updated_at       |
+---------------------+       +------------------+

Megjegyzés: JWT token kezelés nem igényel personal_access_tokens táblát!  
```

---

# I. Modul:   Struktúra kialakítása

## 1. Telepítés (projekt létrehozása, . env konfiguráció, JWT telepítése, tesztútvonal)

`célhely>composer create-project laravel/laravel --prefer-dist workoutProgramJWT`

`célhely>cd workoutProgramJWT`

### JWT csomag telepítése

`workoutProgramJWT>composer require php-open-source-saver/jwt-auth`

`workoutProgramJWT>php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"`

`workoutProgramJWT>php artisan jwt:secret`

*. env fájl módosítása*
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workout_program_jwt
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your_generated_secret_here
JWT_TTL=60
```

*config/app.php módosítása*
```php
'timezone' => 'Europe/Budapest',
```

*config/auth.php módosítása*
```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

*routes/api.php:*
```php
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'message' => 'API works!'
    ], 200);
});
```

### Teszt

**serve**

`workoutProgramJWT>php artisan serve`

> POSTMAN teszt:  GET http://127.0.0.1:8000/api/ping

*VAGY*

**XAMPP**

> POSTMAN teszt: GET http://127.0.0.1/workoutProgramJWT/public/api/ping

---

## 2. Modellek és migráció (sémák)

*users tábla módosítása:  database/migrations/*_create_users_table.php*

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->enum('role', ['student', 'admin'])->default('student');
    $table->integer('age');
    $table->softDeletes();
    $table->timestamps();
});
```

*app/Models/User.php (módosítani kell)*
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'role',
        'age'
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Reláció:  a felhasználó által beiratkozottak az edzések közül.  
     */
    public function enrollments()
    {
        return $this->hasMany(\App\Models\UserWorkout::class, 'user_id');
    }

    /**
     * Many-to-Many reláció: a felhasználó edzéseihez.  
     */
    public function workouts()
    {
        return $this->belongsToMany(\App\Models\Workout::class, 'user_workouts', 'user_id', 'workout_id')
                    ->withPivot('progress', 'last_done', 'completed_at')
                    ->withTimestamps();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
```

`workoutProgramJWT>php artisan make:model Workout -m`

*database/migrations/*_create_workouts_table. php (módosítani kell)*
```php
Schema::create('workouts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('difficulty'); // easy, medium, hard
    $table->timestamps();
});
```

*app/Models/Workout.php (módosítani kell)*
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'difficulty'
    ];

    public function enrollments()
    {
        return $this->hasMany(UserWorkout::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_workouts', 'workout_id', 'user_id')
                    ->withPivot('progress', 'last_done', 'completed_at')
                    ->withTimestamps();
    }
}
```

`workoutProgramJWT>php artisan make:model UserWorkout -m`

*database/migrations/*_create_user_workouts_table.php (módosítani kell)*
```php
Schema::create('user_workouts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('workout_id')->constrained()->onDelete('cascade');
    $table->integer('progress')->default(0); // percentage (0-100)
    $table->date('last_done')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

*app/Models/UserWorkout.php (módosítani kell)*

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWorkout extends Model
{
    use HasFactory;

    protected $table = 'user_workouts';

    protected $fillable = [
        'user_id',
        'workout_id',
        'progress',
        'last_done',
        'completed_at'
    ];

    protected $casts = [
        'last_done' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
```

`workoutProgramJWT>php artisan migrate`

---

## 3. Seeding (Factory és seederek)

*database/factories/UserFactory.php (módosítása)*
```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        $this->faker = \Faker\Factory::create('hu_HU');

        return [
            'name' => $this->faker->firstName .  ' ' . $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail(),
            'age' => $this->faker->numberBetween(18, 65),
            'role' => 'student',
        ];
    }
}
```

`workoutProgramJWT>php artisan make:seeder UserSeeder`

*database/seeders/UserSeeder.php (módosítása)*
```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'age' => 30,
            'role' => 'admin',
        ]);

        // 5 student felhasználó létrehozása
        User::factory(5)->create();
    }
}
```

`workoutProgramJWT>php artisan make:seeder WorkoutSeeder`

*database/seeders/WorkoutSeeder.php (módosítása)*
```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workout;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        Workout::create([
            'title' => 'Kezdő Full Body',
            'description' => 'Teljes test edzés kezdőknek. 3x hetente ajánlott.',
            'difficulty' => 'easy',
        ]);

        Workout::create([
            'title' => 'Haladó erősítő',
            'description' => 'Intenzív erősítő edzés haladóknak.  Súlyzós gyakorlatok.',
            'difficulty' => 'hard',
        ]);

        Workout::create([
            'title' => 'Cardio mix',
            'description' => 'Vegyes kardió edzés.  Futás, ugrókötelezés, burpee.',
            'difficulty' => 'medium',
        ]);
    }
}
```

`workoutProgramJWT>php artisan make:seeder UserWorkoutSeeder`

*database/seeders/UserWorkoutSeeder.php (módosítása)*
```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workout;
use App\Models\UserWorkout;
use Carbon\Carbon;

class UserWorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->take(3)->get();
        $workouts = Workout::all();

        // User 1: két edzésprogram
        UserWorkout::create([
            'user_id' => $users[0]->id,
            'workout_id' => $workouts[0]->id,
            'progress' => 75,
            'last_done' => Carbon::now()->subDays(2),
            'completed_at' => null,
        ]);

        UserWorkout::create([
            'user_id' => $users[0]->id,
            'workout_id' => $workouts[1]->id,
            'progress' => 25,
            'last_done' => Carbon::now()->subDays(5),
            'completed_at' => null,
        ]);

        // User 2: egy befejezett edzésprogram
        UserWorkout::create([
            'user_id' => $users[1]->id,
            'workout_id' => $workouts[0]->id,
            'progress' => 100,
            'last_done' => Carbon::now()->subDay(),
            'completed_at' => Carbon::now()->subDay(),
        ]);

        // User 3: egyik sem teljesített még
        UserWorkout::create([
            'user_id' => $users[2]->id,
            'workout_id' => $workouts[2]->id,
            'progress' => 0,
            'last_done' => null,
            'completed_at' => null,
        ]);
    }
}
```

*database/seeders/DatabaseSeeder.php (módosítása)*
```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WorkoutSeeder::class,
            UserWorkoutSeeder::class,
        ]);
    }
}
```

`workoutProgramJWT>php artisan db:seed`

---

# II. Modul: Controller-ek és endpoint-ok + Admin Middleware

## 1. Admin Middleware létrehozása

`workoutProgramJWT>php artisan make:middleware AdminMiddleware`

*app/Http/Middleware/AdminMiddleware. php*
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
```

*bootstrap/app.php módosítása (Laravel 11) vagy app/Http/Kernel.php (Laravel 10)*

**Laravel 11:**
```php
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath:  dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 2. Controllers

`workoutProgramJWT>php artisan make:controller AuthController`

*app/Http/Controllers/AuthController.php*

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'name' => 'required|string|max:255',
                'age' => 'required|integer|max:80'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Failed to register user',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'age' => $request->age,
            'role' => 'student'
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'role' => $user->role
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid email'], 401);
        }

        // JWT token generálás
        $token = Auth::guard('api')->login($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'role' => $user->role
            ],
            'access' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logout successful']);
    }
}
```

`workoutProgramJWT>php artisan make:controller UserController`

*app/Http/Controllers/UserController.php*
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * GET /users/me
     */
    public function me()
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'stats' => [
                'enrolledCourses' => $user->enrollments()->count(),
                'completedCourses' => $user->enrollments()->whereNotNull('completed_at')->count(),
            ]
        ], 200);
    }

    /**
     * PUT /users/me
     */
    public function updateMe(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        if ($request->name) {
            $user->name = $request->name;
        }
        if ($request->email) {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * GET /users
     */
    public function index()
    {
        $users = User::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        });

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * GET /users/{id}
     */
    public function show($id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->trashed()) {
            return response()->json(['message' => 'User is deleted'], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'stats' => [
                'enrolledCourses' => $user->enrollments()->count(),
                'completedCourses' => $user->enrollments()->whereNotNull('completed_at')->count(),
            ]
        ]);
    }

    /**
     * DELETE /users/{id} - CSAK ADMIN
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
```

`workoutProgramJWT>php artisan make:controller WorkoutController`

*app/Http/Controllers/WorkoutController.php*
```php
<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\UserWorkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutController extends Controller
{
    /**
     * GET /workouts
     */
    public function index()
    {
        $workouts = Workout:: select('id', 'title', 'description', 'difficulty')->get();

        return response()->json([
            'workouts' => $workouts
        ]);
    }

    /**
     * GET /workouts/{workout}
     */
    public function show(Workout $workout)
    {
        $students = $workout->users()
            ->select('name', 'email')
            ->withPivot('progress', 'last_done')
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'progress' => $user->pivot->progress,
                    'last_done' => $user->pivot->last_done,
                ];
            });

        return response()->json([
            'workout' => [
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ],
            'students' => $students
        ]);
    }

    /**
     * POST /workouts - CSAK ADMIN
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
        ]);

        $workout = Workout::create([
            'title' => $request->title,
            'description' => $request->description,
            'difficulty' => $request->difficulty,
        ]);

        return response()->json([
            'message' => 'Workout created successfully',
            'workout' => [
                'id' => $workout->id,
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ]
        ], 201);
    }

    /**
     * PUT /workouts/{workout} - CSAK ADMIN
     */
    public function update(Request $request, Workout $workout)
    {
        $request->validate([
            'title' => 'sometimes|string|max: 255',
            'description' => 'nullable|string',
            'difficulty' => 'sometimes|in:easy,medium,hard',
        ]);

        $workout->update($request->only(['title', 'description', 'difficulty']));

        return response()->json([
            'message' => 'Workout updated successfully',
            'workout' => [
                'id' => $workout->id,
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ]
        ]);
    }

    /**
     * DELETE /workouts/{workout} - CSAK ADMIN
     */
    public function destroy(Workout $workout)
    {
        $workout->delete();

        return response()->json([
            'message' => 'Workout deleted successfully'
        ]);
    }

    /**
     * POST /workouts/{workout}/enroll
     */
    public function enroll(Workout $workout)
    {
        $user = Auth:: guard('api')->user();

        if ($user->workouts()->where('workout_id', $workout->id)->exists()) {
            return response()->json(['message' => 'Already enrolled'], 422);
        }

        $user->workouts()->attach($workout->id, [
            'progress' => 0,
            'last_done' => null
        ]);

        return response()->json(['message' => 'Enrolled successfully'], 201);
    }

    /**
     * POST /workouts/{workout}/complete
     */
    public function complete(Workout $workout)
    {
        $user = Auth:: guard('api')->user();

        $record = UserWorkout::where('user_id', $user->id)
            ->where('workout_id', $workout->id)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Not enrolled'], 404);
        }

        $record->update([
            'progress' => 100,
            'last_done' => now(),
            'completed_at' => now()
        ]);

        return response()->json([
            'message' => 'Workout marked as completed'
        ]);
    }
}
```

---

## 3. Routes

*routes/api.php*
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkoutController;

// -------------------------
// PUBLIC ROUTES
// -------------------------
Route::get('/ping', function () {
    return response()->json(['message' => 'API works! ']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// -------------------------
// AUTHENTICATED ROUTES (JWT)
// -------------------------
Route:: middleware('auth:api')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // User
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/me', [UserController::class, 'updateMe']);

    // User listing
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    
    // Admin Only:  User delete
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('admin');

    // Workouts
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show']);
    
    // Admin Only: Workout CRUD
    Route::post('/workouts', [WorkoutController::class, 'store'])->middleware('admin');
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update'])->middleware('admin');
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->middleware('admin');
    
    // Enrollment
    Route::post('/workouts/{workout}/enroll', [WorkoutController::class, 'enroll']);
    Route::post('/workouts/{workout}/complete', [WorkoutController::class, 'complete']);
});
```

---

# III. Modul: Tesztelés

`workoutProgramJWT>php artisan make:test AuthTest`

*tests/Feature/AuthTest.php*
```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_ok()
    {
        $response = $this->getJson('/api/ping');
        $response->assertStatus(200)
                ->assertJson(['message' => 'API works!']);
    }

    public function test_register_creates_user()
    {
        $payload = [
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'age' => 30
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201)
                ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email', 'age', 'role']]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'teszt@example.com',
        ]);
    }

    public function test_login_with_valid_email_returns_jwt_token()
    {
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'validuser@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message', 
                     'user' => ['id', 'name', 'email', 'age', 'role'], 
                     'access' => ['token', 'token_type', 'expires_in']
                 ]);
    }

    public function test_login_with_invalid_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid email']);
    }
}
```

`workoutProgramJWT>php artisan make:test UserTest`

*tests/Feature/UserTest.php*
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser($user)
    {
        $token = Auth::guard('api')->login($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_me_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/users/me');
        $response->assertStatus(401);
    }

    public function test_me_endpoint_returns_user_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAsUser($user)->getJson('/api/users/me');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'stats' => ['enrolledCourses', 'completedCourses']
                 ])
                 ->assertJsonPath('user.email', $user->email);
    }

    public function test_user_can_update_their_own_profile()
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $newEmail = 'new@example.com';
        $newName = 'New Name';

        $response = $this->actingAsUser($user)->putJson('/api/users/me', [
            'name' => $newName,
            'email' => $newEmail,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile updated successfully'])
                 ->assertJsonPath('user.name', $newName)
                 ->assertJsonPath('user.email', $newEmail);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newName,
            'email' => $newEmail,
        ]);
    }

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userToDelete = User::factory()->create();

        $response = $this->actingAsUser($admin)->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully']);

        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }

    public function test_student_cannot_delete_user()
    {
        $student = User::factory()->create(['role' => 'student']);
        $userToDelete = User::factory()->create();

        $response = $this->actingAsUser($student)->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized.  Admin access required.']);
    }
}
```

`workoutProgramJWT>php artisan make:test WorkoutTest`

*tests/Feature/WorkoutTest.php*
```php
<?php

namespace Tests\Feature;

use App\Models\Workout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser($user)
    {
        $token = Auth::guard('api')->login($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_workout_index_requires_authentication()
    {
        $response = $this->getJson('/api/workouts');
        $response->assertStatus(401);
    }

    public function test_workout_index_returns_list_of_workouts()
    {
        $user = User:: factory()->create();
        
        Workout::create(['title' => 'Workout A', 'description' => 'Desc A', 'difficulty' => 'easy']);
        Workout::create(['title' => 'Workout B', 'description' => 'Desc B', 'difficulty' => 'medium']);

        $response = $this->actingAsUser($user)->getJson('/api/workouts');

        $response->assertStatus(200)
                 ->assertJsonStructure(['workouts' => [
                     '*' => ['id', 'title', 'description', 'difficulty']
                 ]]);
    }

    public function test_admin_can_create_workout()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'title' => 'New Workout',
            'description' => 'Test workout',
            'difficulty' => 'medium',
        ];

        $response = $this->actingAsUser($admin)->postJson('/api/workouts', $payload);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Workout created successfully']);

        $this->assertDatabaseHas('workouts', ['title' => 'New Workout']);
    }

    public function test_student_cannot_create_workout()
    {
        $student = User::factory()->create(['role' => 'student']);

        $payload = [
            'title' => 'New Workout',
            'description' => 'Test workout',
            'difficulty' => 'medium',
        ];

        $response = $this->actingAsUser($student)->postJson('/api/workouts', $payload);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized. Admin access required.']);
    }

    public function test_user_can_enroll_in_a_workout()
    {
        $user = User::factory()->create();
        $workout = Workout:: create(['title' => 'Enroll Test', 'description' => 'Desc', 'difficulty' => 'easy']);

        $response = $this->actingAsUser($user)->postJson("/api/workouts/{$workout->id}/enroll");

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Enrolled successfully']);

        $this->assertDatabaseHas('user_workouts', [
            'user_id' => $user->id,
            'workout_id' => $workout->id,
        ]);
    }

    public function test_user_can_complete_an_enrolled_workout()
    {
        $user = User::factory()->create();
        $workout = Workout::create(['title' => 'Complete Test', 'description' => 'Desc', 'difficulty' => 'hard']);
        
        $user->workouts()->attach($workout->id, ['progress' => 0]);

        $response = $this->actingAsUser($user)->postJson("/api/workouts/{$workout->id}/complete");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Workout marked as completed']);

        $this->assertDatabaseHas('user_workouts', [
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'progress' => 100,
        ]);
    }
}
```

`workoutProgramJWT>php artisan test`

---
