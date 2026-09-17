<?php

/*
 * Ejemplo de cómo debe quedar tu App\Models\User después de agregar la
 * relación profile(). Se muestra el archivo completo como referencia, pero
 * solo necesitas agregar el `use HasOne` y el método profile() a tu propio
 * User.php (esta versión usa la sintaxis moderna con atributos
 * #[Fillable]/#[Hidden] en vez de las propiedades clásicas $fillable/$hidden,
 * disponible en Laravel 12).
 */

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
