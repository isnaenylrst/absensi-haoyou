<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil.edit', [
            'employee' => $request->user()->employee,
        ]);
    }

    /** Hanya Owner boleh sampai ke sini (dijaga middleware role:owner di route) */
    public function update(Request $request)
    {
        $employee = $request->user()->employee;
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
        ]);

        $employee->update($data);

        return back()->with('status', 'Data kontak berhasil diperbarui.');
    }

    /** Semua role boleh ganti password sendiri */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Password lama salah.'])->withInput();
        }

        $user->password_hash = Hash::make($data['password']);
        $user->save();

        return back()->with('status', 'Password berhasil diubah.');
    }

    /** Semua role boleh upload/ganti foto profil sendiri */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $employee = $request->user()->employee;

        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $path = $request->file('photo')->store('profil-foto', 'public');
        $employee->update(['photo_path' => $path]);

        return back()->with('status', 'Foto profil berhasil diperbarui.');
    }

    /** Semua role boleh hapus foto profil sendiri */
    public function removePhoto(Request $request)
    {
        $employee = $request->user()->employee;

        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $employee->update(['photo_path' => null]);

        return back()->with('status', 'Foto profil dihapus.');
    }
}