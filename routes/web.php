<?php

use App\Http\Controllers\IpAiController;
use App\Http\Controllers\IpTracingController;
use App\Http\Controllers\TeamsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IpTracingController::class, 'index'])->name('ip.dashboard');

// Batch scan
Route::post('/scan/range', [IpTracingController::class, 'scanRange'])->name('ip.scan.range');

// Single IP scan (full)
Route::post('/scan/ip', [IpTracingController::class, 'scanSingleIp'])->name('ip.scan.single');

// Separate scan methods (focused & fast)
Route::post('/scan/ping', [IpTracingController::class, 'pingSingleIp'])->name('ip.scan.ping');
Route::post('/scan/hostname', [IpTracingController::class, 'hostnameSingleIp'])->name('ip.scan.hostname');
Route::post('/scan/mac', [IpTracingController::class, 'macSingleIp'])->name('ip.scan.mac');

// Excel update
Route::post('/excel/update', [IpTracingController::class, 'updateExcel'])->name('ip.excel.update');

// AI Network Agent Routes
Route::post('/ai/diagnose', [IpAiController::class, 'diagnoseSingleIp'])->name('ip.ai.diagnose');
Route::post('/ai/chat', [IpAiController::class, 'chat'])->name('ip.ai.chat');

// GitHub Data Sync
Route::post('/github/sync', [IpTracingController::class, 'syncGithub'])->name('ip.github.sync');

// Microsoft Teams Webhook Route
Route::post('/api/teams/webhook', [TeamsWebhookController::class, 'handle'])->name('teams.webhook');

