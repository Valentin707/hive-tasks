<?php

use App\Http\Controllers\NotesController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WorkspaceController::class, 'index'])->name('dashboard');
Route::get('/projects', [WorkspaceController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}/boards/{board}', [WorkspaceController::class, 'showBoard'])->name('projects.board');
Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
