@extends('layouts.app')

@section('content')
<div class="container">
  <div class="card">

    <div class="card-title">Profile</div>
    <p style="color:#6b7280; margin-top:4px;">
      Update informasi akun kamu.
    </p>

    <hr style="margin:16px 0;">

    {{-- Update Name & Email --}}
    <form method="POST" action="{{ route('profile.update') }}" style="max-width:400px;">
      @csrf
      @method('PATCH')

      <div style="margin-bottom:12px;">
        <label class="text-sm font-semibold">Name</label>
        <input class="input" name="name" value="{{ old('name', auth()->user()->name) }}" required>
      </div>

      <div style="margin-bottom:12px;">
        <label class="text-sm font-semibold">Email</label>
        <input class="input" name="email" type="email"
               value="{{ old('email', auth()->user()->email) }}" required>
      </div>

      <button class="btn btn-primary">Save</button>
      <hr style="margin:16px 0;">

<div class="card-title">Academic Information</div>

<form method="POST" action="{{ route('profile.update') }}" style="max-width:420px;">
  @csrf
  @method('PATCH')

  <div class="form-group">
    <label>Campus</label>
    <input class="input" name="campus"
           value="{{ old('campus', auth()->user()->campus) }}"
           placeholder="Nama Kampus">
  </div>

  <div class="form-group">
    <label>NIM</label>
    <input class="input" name="student_id"
           value="{{ old('student_id', auth()->user()->student_id) }}"
           placeholder="Contoh: 221011234">
  </div>

  <div class="form-group">
    <label>Class</label>
    <input class="input" name="class"
           value="{{ old('class', auth()->user()->class) }}"
           placeholder="Contoh: IF-4A">
  </div>

  <button class="btn btn-primary">Save Academic Info</button>
</form>

    </form>

    <hr style="margin:16px 0;">

    {{-- Change password --}}
    <div class="card-title">Change Password</div>
    <form method="POST" action="{{ route('password.update') }}" style="max-width:400px; margin-top:8px;">
      @csrf
      @method('PUT')

      <div style="margin-bottom:12px;">
        <label class="text-sm font-semibold">Current Password</label>
        <input class="input" type="password" name="current_password" required>
      </div>

      <div style="margin-bottom:12px;">
        <label class="text-sm font-semibold">New Password</label>
        <input class="input" type="password" name="password" required>
      </div>

      <div style="margin-bottom:12px;">
        <label class="text-sm font-semibold">Confirm Password</label>
        <input class="input" type="password" name="password_confirmation" required>
      </div>

      <button class="btn">Update Password</button>
    </form>

  </div>
</div>
@endsection
