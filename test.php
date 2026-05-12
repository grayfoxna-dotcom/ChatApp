<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(1);
$suggestedUser = \App\Models\User::find(4);
$has1on1 = $suggestedUser->conversations()->where('is_group', false)->whereHas('users', function($uq) use ($user) { $uq->where('users.id', $user->id); })->get();
dd($has1on1->toArray());
