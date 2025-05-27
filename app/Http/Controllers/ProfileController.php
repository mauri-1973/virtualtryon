<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        // Para este ejemplo, usaremos datos simulados
        // En una aplicación real, obtendrías los datos del usuario autenticado
        $user = (object) [
            'id' => 1,
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'phone' => '(555) 123-4567',
            'address' => '123 Main St, Anytown, CA 12345',
            'avatar' => null
        ];

        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        // En una aplicación real, actualizarías el usuario autenticado
        // $user = auth()->user();
        // $user->update($request->only(['name', 'email', 'phone', 'address']));

        // Manejar avatar si se proporciona
        if ($request->hasFile('avatar')) {
            // $avatarPath = $request->file('avatar')->store('avatars', 'public');
            // $user->update(['avatar' => $avatarPath]);
        }

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed'
        ]);

        // En una aplicación real, verificarías la contraseña actual
        // if (!Hash::check($request->current_password, auth()->user()->password)) {
        //     return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
        // }

        // auth()->user()->update([
        //     'password' => Hash::make($request->new_password)
        // ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
