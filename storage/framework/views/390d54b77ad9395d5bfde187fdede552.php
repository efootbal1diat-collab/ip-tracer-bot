<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>IP Network Tracing & Management</title>

    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Product+Sans:wght@400;500;700&family=Google+Sans:wght@400;500;700&family=Google+Sans+Text:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Google Sans"', '"Google Sans Text"', '"Product Sans"', 'sans-serif'],
                    },
                    colors: {
                        googleBg: '#F8FAFD',
                        googleCard: '#FFFFFF',
                        googleBorder: '#E0E3E7',
                        googleBlue: '#1A73E8',
                        googleBlueHover: '#1765CC',
                        googleGreen: '#137333',
                        googleGreenBg: '#E6F4EA',
                        googleAmber: '#B06000',
                        googleAmberBg: '#FEF7E0',
                        googleGray: '#5F6368',
                        googleDarkText: '#202124',
                    }
                }
            }
        }
    </script>
    <!-- Marked.js for AI Markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        @font-face {
            font-family: 'Google Sans';
            src: url('https://fonts.gstatic.com/s/googlesans/v54/4UaGrENHsxJlGDuGo1OIlL3Kwp5MKg.woff2') format('woff2');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Google Sans';
            src: url('https://fonts.gstatic.com/s/googlesans/v54/4UabrENHsxJlGDuGo1OIlLU94Yt3CwZ-Pw.woff2') format('woff2');
            font-weight: 500;
        }
        @font-face {
            font-family: 'Google Sans';
            src: url('https://fonts.gstatic.com/s/googlesans/v54/4UabrENHsxJlGDuGo1OIlLV14Yt3CwZ-Pw.woff2') format('woff2');
            font-weight: 700;
        }
        body, input, button, select, textarea, th, td, h1, h2, h3, h4, p, span, div, a {
            font-family: 'Google Sans', 'Google Sans Text', 'Product Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .fa, .fas, .far, .fal, .fad, .fab, .bi, [class*="fa-"], [class*="bi-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "bootstrap-icons" !important;
        }
        body {
            background-color: #F8FAFD;
            background-image:
                linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
            background-size: 28px 28px;
            color: #202124;
        }
        header {
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
        }
        .google-card {
            background: #FFFFFF;
            border: 1px solid #E0E3E7;
            border-radius: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .google-card:hover {
            border-color: #B0B5BC;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.1), 0 4px 8px -2px rgba(0, 0, 0, 0.06);
        }
        .btn-google { background-color: #1A73E8; color: #FFFFFF; font-weight: 500; border-radius: 20px; transition: all 0.15s ease; }
        .btn-google:hover { background-color: #1765CC; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .btn-google:disabled { background-color: #94B8E8; cursor: not-allowed; }
        .badge-google { border-radius: 16px; font-weight: 500; padding: 2px 10px; font-size: 11px; }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }

        /* AI Markdown styling */
        .ai-markdown-content { font-size: 13px; line-height: 1.6; color: #1f2937; }
        .ai-markdown-content h1, .ai-markdown-content h2, .ai-markdown-content h3 { font-weight: 700; color: #111827; margin-top: 0.75rem; margin-bottom: 0.25rem; }
        .ai-markdown-content h1 { font-size: 1.1rem; }
        .ai-markdown-content h2 { font-size: 1rem; }
        .ai-markdown-content h3 { font-size: 0.9rem; }
        .ai-markdown-content ul, .ai-markdown-content ol { padding-left: 1.25rem; margin-bottom: 0.5rem; }
        .ai-markdown-content ul { list-style-type: disc; }
        .ai-markdown-content ol { list-style-type: decimal; }
        .ai-markdown-content li { margin-bottom: 0.25rem; }
        .ai-markdown-content p { margin-bottom: 0.5rem; }
        .ai-markdown-content code { background: #f3f4f6; color: #4338ca; padding: 0.15rem 0.35rem; border-radius: 4px; font-family: monospace; font-size: 12px; }
        .ai-markdown-content pre { background: #1f2937; color: #f9fafb; padding: 0.75rem; border-radius: 8px; overflow-x: auto; margin-bottom: 0.5rem; }
        .ai-markdown-content pre code { background: transparent; color: inherit; padding: 0; }
        .ai-markdown-content blockquote { border-left: 3px solid #6366f1; padding-left: 0.75rem; margin: 0.5rem 0; color: #4b5563; font-style: italic; }
        .ai-markdown-content strong { color: #111827; font-weight: 600; }
    </style>
</head>
<body class="min-h-screen pb-16">

    <!-- Top Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 px-6 py-3 shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-network-wired text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900 tracking-tight flex items-center gap-2">
                        IP Tracing Manager
                        <form method="GET" action="/">
                            <select name="subnet" onchange="this.form.submit()" class="text-xs bg-blue-100 text-blue-800 font-medium px-2.5 py-1 rounded-full border border-blue-200 focus:outline-none cursor-pointer">
                                <?php $__currentLoopData = $subnets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($s); ?>" <?php echo e($activeSubnet === $s ? 'selected' : ''); ?>>Subnet <?php echo e($s); ?>.xxx</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </form>
                    </h1>
                    <p class="text-xs text-gray-500">
                        Last scan: <span class="font-semibold text-gray-700"><?php echo e($lastScanTime); ?></span>
                        | Excel: <span class="text-gray-600">001. Data User IP.xlsx</span>
                    </p>
                </div>
            </div>

            <!-- Action Header Buttons -->
            <div class="flex items-center space-x-2">
                <button onclick="startBatchPing(1, 254)" id="btnBatchPing" class="btn-google px-4 py-2 text-sm flex items-center gap-2 shadow-sm bg-green-600 hover:bg-green-700">
                    <i class="fa-solid fa-wifi" id="btnBatchPingIcon"></i>
                    <span id="btnBatchPingText">Ping Jaringan</span>
                </button>
                <button onclick="startBatchHostname(1, 254)" id="btnBatchHostname" class="btn-google px-4 py-2 text-sm flex items-center gap-2 shadow-sm bg-blue-600 hover:bg-blue-700">
                    <i class="fa-solid fa-desktop" id="btnBatchHostnameIcon"></i>
                    <span id="btnBatchHostnameText">Hostname</span>
                </button>
                <button onclick="startBatchMac(1, 254)" id="btnBatchMac" class="btn-google px-4 py-2 text-sm flex items-center gap-2 shadow-sm bg-purple-600 hover:bg-purple-700">
                    <i class="fa-solid fa-network-wired" id="btnBatchMacIcon"></i>
                    <span id="btnBatchMacText">MAC + Vendor</span>
                </button>
                <button onclick="toggleAiChat(true)" id="btnOpenAiCopilotHeader" class="btn-google px-4 py-2 text-sm flex items-center gap-2 shadow-sm bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold">
                    <i class="fa-solid fa-robot text-yellow-300"></i>
                    <span>AI Copilot</span>
                </button>
                <button onclick="location.reload()" class="p-2.5 text-gray-600 hover:bg-gray-100 rounded-full transition" title="Refresh Halaman">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-6 mt-6 space-y-6">

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="google-card p-4 flex items-center space-x-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">254</div>
                    <div class="text-xs text-gray-500 font-medium">Total IP</div>
                </div>
            </div>

            <div class="google-card p-4 flex items-center space-x-4">
                <div class="p-3 bg-green-50 text-green-700 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-700" id="statActive"><?php echo e($summaryStats['active_count']); ?></div>
                    <div class="text-xs text-gray-500 font-medium">Online / Aktif</div>
                </div>
            </div>

            <div class="google-card p-4 flex items-center space-x-4">
                <div class="p-3 bg-amber-50 text-amber-700 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-amber-700"><?php echo e($summaryStats['unmapped_active']); ?></div>
                    <div class="text-xs text-gray-500 font-medium">Aktif (Excel Kosong)</div>
                </div>
            </div>

            <div class="google-card p-4 flex items-center space-x-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-indigo-600"><?php echo e($summaryStats['excel_mapped']); ?></div>
                    <div class="text-xs text-gray-500 font-medium">Terdaftar (Excel)</div>
                </div>
            </div>

            <div class="google-card p-4 flex items-center space-x-4">
                <div class="p-3 bg-gray-100 text-gray-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-600"><?php echo e($summaryStats['free_count']); ?></div>
                    <div class="text-xs text-gray-500 font-medium">IP Free / Kosong</div>
                </div>
            </div>
        </div>

        <!-- Toolbar: Search, View Switcher, Filter -->
        <div class="google-card p-3 flex flex-col md:flex-row items-center justify-between gap-4">

            <!-- Left: Search & View Toggle -->
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-sm"></i>
                    <input type="text" id="searchInput" onkeyup="filterIpCards()" placeholder="Cari IP, Machine, User, Hostname..." class="w-full bg-gray-50 border border-gray-300 rounded-full pl-10 pr-4 py-2 text-sm text-gray-800 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                </div>

                <div class="bg-gray-100 p-1 rounded-lg flex items-center space-x-1 border border-gray-200">
                    <button onclick="setViewMode('table')" id="btnViewTable" class="px-3 py-1.5 rounded-md text-xs font-semibold bg-white text-gray-800 shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-list"></i> Table
                    </button>
                    <button onclick="setViewMode('grid')" id="btnViewGrid" class="px-3 py-1.5 rounded-md text-xs font-semibold text-gray-600 hover:text-gray-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-border-all"></i> Grid
                    </button>
                </div>
            </div>

            <!-- Right: Filter Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <button onclick="setFilterCategory('all')" id="tab-all" class="px-3.5 py-1.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200">Semua (254)</button>
                <button onclick="setFilterCategory('active_matched')" id="tab-active_matched" class="px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-700 font-semibold border border-gray-200 hover:bg-gray-200">Aktif & Terdaftar</button>
                <button onclick="setFilterCategory('active_unmapped')" id="tab-active_unmapped" class="px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-700 font-semibold border border-gray-200 hover:bg-gray-200">Aktif (Excel Kosong)</button>
                <button onclick="setFilterCategory('offline_mapped')" id="tab-offline_mapped" class="px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-700 font-semibold border border-gray-200 hover:bg-gray-200">Offline (Terdaftar)</button>
                <button onclick="setFilterCategory('free_ip')" id="tab-free_ip" class="px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-700 font-semibold border border-gray-200 hover:bg-gray-200">IP Kosong</button>
            </div>
        </div>

        <!-- Scan Progress -->
        <div id="scanProgressContainer" class="hidden google-card p-4 border border-blue-200 bg-blue-50/50">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-blue-700 font-medium flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span id="scanProgressTitle">Memindai Jaringan Subnet <?php echo e($activeSubnet); ?>.xxx...</span>
                </span>
                <span id="scanStatusText" class="text-gray-600 font-mono text-xs">Memulai...</span>
            </div>
            <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
                <div id="scanProgressBar" class="bg-blue-600 h-full w-0 rounded-full transition-all duration-500 ease-out"></div>
            </div>
            <p id="scanProgressDetail" class="text-xs text-gray-500 mt-2"></p>
        </div>

        <!-- TABLE VIEW -->
        <div id="tableViewContainer" class="google-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-32">IP Address</th>
                            <th class="py-3.5 px-4 w-28">Status</th>
                            <th class="py-3.5 px-4">Hostname PC</th>
                            <th class="py-3.5 px-4">Machine (Excel)</th>
                            <th class="py-3.5 px-4">User / PJ</th>
                            <th class="py-3.5 px-4">OS / Perangkat</th>
                            <th class="py-3.5 px-4">Vendor (IEEE MAC)</th>
                            <th class="py-3.5 px-4 w-40">MAC Address</th>
                            <th class="py-3.5 px-4 w-20">Resp.</th>
                            <th class="py-3.5 px-4 text-right w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="ipTableBody" class="divide-y divide-gray-100">
                        <?php $__currentLoopData = $ipList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isUnmapped = $item['status_category'] === 'active_unmapped';
                                $isMatched = $item['status_category'] === 'active_matched';
                                $isOfflineMapped = $item['status_category'] === 'offline_mapped';
                                $rowBg = $isUnmapped ? 'bg-amber-50/40' : ($isMatched ? 'bg-green-50/20' : '');
                            ?>
                            <tr id="row-ip-<?php echo e($item['ip_suffix']); ?>" class="hover:bg-blue-50/40 transition-colors <?php echo e($rowBg); ?>"
                                data-suffix="<?php echo e($item['ip_suffix']); ?>"
                                data-ip="<?php echo e($item['full_ip']); ?>"
                                data-machine="<?php echo e(strtolower($item['excel_machine'] ?? '')); ?>"
                                data-user="<?php echo e(strtolower($item['excel_user'] ?? '')); ?>"
                                data-hostname="<?php echo e(strtolower($item['hostname'] ?? '')); ?>"
                                data-category="<?php echo e($item['status_category']); ?>">

                                <!-- IP -->
                                <td class="py-3 px-4 font-mono font-semibold text-gray-900">
                                    <?php echo e($activeSubnet); ?>.<span class="text-blue-600 font-bold"><?php echo e($item['ip_suffix']); ?></span>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4 status-cell" id="status-cell-<?php echo e($item['ip_suffix']); ?>">
                                    <?php if($item['is_active']): ?>
                                        <span class="badge-google inline-flex items-center gap-1.5 bg-green-100 text-green-800">
                                            <span class="h-2 w-2 rounded-full bg-green-600 pulse-dot"></span> ONLINE
                                        </span>
                                    <?php elseif($item['status_category'] === 'unknown'): ?>
                                        <span class="badge-google bg-gray-100 text-gray-400 font-normal italic">BELUM SCAN</span>
                                    <?php else: ?>
                                        <span class="badge-google bg-gray-100 text-gray-500 font-normal">OFFLINE</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Hostname PC -->
                                <td class="py-3 px-4 hostname-cell" id="hostname-cell-<?php echo e($item['ip_suffix']); ?>">
                                    <span class="font-mono text-xs <?php echo e($item['hostname'] ? 'text-indigo-700 bg-indigo-50 font-semibold px-2 py-0.5 rounded border border-indigo-100' : 'text-gray-400 italic'); ?>">
                                        <?php echo e($item['hostname'] ?: '-'); ?>

                                    </span>
                                </td>

                                <!-- Machine -->
                                <td class="py-3 px-4">
                                    <span class="font-medium <?php echo e($item['excel_machine'] ? 'text-gray-900' : 'text-gray-400 italic'); ?>">
                                        <?php echo e($item['excel_machine'] ?: '(Kosong)'); ?>

                                    </span>
                                </td>

                                <!-- User -->
                                <td class="py-3 px-4">
                                    <span class="font-medium <?php echo e($item['excel_user'] ? 'text-gray-800' : 'text-gray-400 italic'); ?>">
                                        <?php echo e($item['excel_user'] ?: '(Kosong)'); ?>

                                    </span>
                                </td>

                                <!-- OS / Perangkat -->
                                <td class="py-3 px-4 text-xs text-gray-600 device-cell">
                                    <?php echo e($item['device_type'] !== 'Unknown' ? $item['device_type'] : '-'); ?>

                                </td>

                                <!-- Vendor (IEEE MAC) -->
                                <td class="py-3 px-4 text-xs vendor-cell" id="vendor-cell-<?php echo e($item['ip_suffix']); ?>">
                                    <?php if($item['vendor']): ?>
                                        <div class="font-medium text-gray-900">
                                            <span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mr-1">
                                                <?php echo e($item['vendor']); ?>

                                            </span>
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">
                                            <i class="fa-solid fa-microchip text-blue-500 mr-1"></i> <?php echo e($item['probable_device']); ?>

                                        </div>
                                    <?php elseif($item['mac_address']): ?>
                                        <span class="text-gray-400 text-[11px] font-mono" data-mac="<?php echo e($item['mac_address']); ?>" data-suffix="<?php echo e($item['ip_suffix']); ?>">Perangkat Jaringan</span>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- MAC Address -->
                                <td class="py-3 px-4 font-mono text-xs text-gray-500 mac-cell" id="mac-cell-<?php echo e($item['ip_suffix']); ?>">
                                    <?php echo e($item['mac_address'] ?: '-'); ?>

                                </td>

                                <!-- Response Time -->
                                <td class="py-3 px-4 text-xs font-mono response-cell" id="resp-cell-<?php echo e($item['ip_suffix']); ?>">
                                    <?php if($item['response_time_ms']): ?>
                                        <span class="text-green-700 font-semibold"><?php echo e($item['response_time_ms']); ?>ms</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right space-x-1">
                                    <button onclick="diagnoseWithAi(<?php echo e($item['ip_suffix']); ?>)" id="row-btn-ai-<?php echo e($item['ip_suffix']); ?>" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md transition" title="Diagnosa AI">
                                        <i class="fa-solid fa-robot"></i>
                                    </button>
                                    <button onclick="pingSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="row-btn-ping-<?php echo e($item['ip_suffix']); ?>" class="p-1.5 text-green-600 hover:bg-green-50 rounded-md transition" title="Ping Saja">
                                        <i class="fa-solid fa-wifi"></i>
                                    </button>
                                    <button onclick="hostnameSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="row-btn-hostname-<?php echo e($item['ip_suffix']); ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md transition" title="Hostname + OS">
                                        <i class="fa-solid fa-desktop"></i>
                                    </button>
                                    <button onclick="macSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="row-btn-mac-<?php echo e($item['ip_suffix']); ?>" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-md transition" title="MAC + Vendor">
                                        <i class="fa-solid fa-network-wired"></i>
                                    </button>
                                    <button onclick="openEditModal(<?php echo e($item['ip_suffix']); ?>, '<?php echo e(addslashes($item['excel_machine'] ?? '')); ?>', '<?php echo e(addslashes($item['excel_user'] ?? '')); ?>', '<?php echo e(addslashes($item['excel_windows'] ?? '')); ?>')" class="p-1.5 text-gray-700 hover:bg-gray-100 rounded-md transition" title="Edit Excel">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GRID VIEW -->
        <div id="gridViewContainer" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php $__currentLoopData = $ipList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isUnmapped = $item['status_category'] === 'active_unmapped';
                    $isMatched = $item['status_category'] === 'active_matched';
                    $cardBorder = $isUnmapped ? 'border-amber-300 bg-amber-50/20' : ($isMatched ? 'border-green-300 bg-green-50/20' : '');
                ?>
                <div class="google-card p-4 flex flex-col justify-between <?php echo e($cardBorder); ?>"
                    data-suffix="<?php echo e($item['ip_suffix']); ?>"
                    data-ip="<?php echo e($item['full_ip']); ?>"
                    data-machine="<?php echo e(strtolower($item['excel_machine'] ?? '')); ?>"
                    data-user="<?php echo e(strtolower($item['excel_user'] ?? '')); ?>"
                    data-hostname="<?php echo e(strtolower($item['hostname'] ?? '')); ?>"
                    data-category="<?php echo e($item['status_category']); ?>">

                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono text-sm font-bold text-gray-900">
                            <?php echo e($activeSubnet); ?>.<span class="text-blue-600 text-base"><?php echo e($item['ip_suffix']); ?></span>
                        </span>
                        <?php if($item['is_active']): ?>
                            <span class="badge-google bg-green-100 text-green-800 inline-flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-600 pulse-dot"></span> ONLINE
                            </span>
                        <?php else: ?>
                            <span class="badge-google bg-gray-100 text-gray-500 font-normal">OFFLINE</span>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-1 text-xs mb-3">
                        <div class="flex justify-between"><span class="text-gray-500">Hostname:</span> <span class="font-semibold text-indigo-700 font-mono"><?php echo e($item['hostname'] ?: '-'); ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Machine:</span> <span class="font-semibold text-gray-900"><?php echo e($item['excel_machine'] ?: '(Kosong)'); ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">User:</span> <span class="font-semibold text-gray-800"><?php echo e($item['excel_user'] ?: '(Kosong)'); ?></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">MAC:</span> <span class="font-mono text-gray-600"><?php echo e($item['mac_address'] ?: '-'); ?></span></div>
                        <?php if($item['response_time_ms']): ?>
                            <div class="flex justify-between"><span class="text-gray-500">Response:</span> <span class="font-semibold text-green-700"><?php echo e($item['response_time_ms']); ?>ms</span></div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-end space-x-2 border-t border-gray-100 pt-2">
                        <button onclick="diagnoseWithAi(<?php echo e($item['ip_suffix']); ?>)" id="grid-btn-ai-<?php echo e($item['ip_suffix']); ?>" class="px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-50 rounded-md font-medium" title="Diagnosa AI"><i class="fa-solid fa-robot"></i></button>
                        <button onclick="pingSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="grid-btn-ping-<?php echo e($item['ip_suffix']); ?>" class="px-2 py-1 text-xs text-green-600 hover:bg-green-50 rounded-md font-medium" title="Ping"><i class="fa-solid fa-wifi"></i></button>
                        <button onclick="hostnameSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="grid-btn-hostname-<?php echo e($item['ip_suffix']); ?>" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded-md font-medium" title="Hostname"><i class="fa-solid fa-desktop"></i></button>
                        <button onclick="macSingleIp(<?php echo e($item['ip_suffix']); ?>)" id="grid-btn-mac-<?php echo e($item['ip_suffix']); ?>" class="px-2 py-1 text-xs text-purple-600 hover:bg-purple-50 rounded-md font-medium" title="MAC"><i class="fa-solid fa-network-wired"></i></button>
                        <button onclick="openEditModal(<?php echo e($item['ip_suffix']); ?>, '<?php echo e(addslashes($item['excel_machine'] ?? '')); ?>', '<?php echo e(addslashes($item['excel_user'] ?? '')); ?>', '<?php echo e(addslashes($item['excel_windows'] ?? '')); ?>')" class="px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded-md font-medium">Edit</button>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

    </main>

    <!-- Edit Excel Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-gray-200">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-green-600"></i> Update Excel — IP <span id="modalIpDisplay" class="text-blue-600"><?php echo e($activeSubnet); ?>.X</span>
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form id="editForm" onsubmit="saveExcelData(event)" class="space-y-4">
                <input type="hidden" id="modalIpSuffix">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Machine / Computer Name</label>
                    <input type="text" id="modalMachine" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2 text-sm text-gray-900 focus:bg-white focus:border-blue-500 focus:outline-none" placeholder="Contoh: KIAS-PC-0123">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama User / Penanggung Jawab</label>
                    <input type="text" id="modalUser" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2 text-sm text-gray-900 focus:bg-white focus:border-blue-500 focus:outline-none" placeholder="Contoh: Budi / IT Staff">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sistem Operasi / Perangkat</label>
                    <input type="text" id="modalWindows" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2 text-sm text-gray-900 focus:bg-white focus:border-blue-500 focus:outline-none" placeholder="Contoh: WIN 11 / SERVER / PRINTER">
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-full text-xs font-semibold">Batal</button>
                    <button type="submit" class="btn-google px-5 py-2 text-xs font-semibold shadow-sm">Simpan Ke Excel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scan Result Modal -->
    <div id="scanModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-xl border border-gray-200 overflow-hidden">

            <!-- Header -->
            <div id="scanModalHeader" class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-blue-600"></i>
                    <span>Hasil Scan — </span>
                    <span id="scanModalIp" class="text-blue-600"><?php echo e($activeSubnet); ?>.X</span>
                </h3>
                <button onclick="closeScanModal()" class="text-gray-400 hover:text-gray-600 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-4">

                <!-- Status Banner -->
                <div id="scanStatusBanner" class="hidden flex items-center gap-3 p-3 rounded-xl border">
                    <div id="scanStatusIcon" class="w-10 h-10 rounded-full flex items-center justify-center text-white">
                        <i class="fa-solid fa-check text-lg"></i>
                    </div>
                    <div>
                        <div id="scanModalStatusText" class="text-sm font-bold">ONLINE</div>
                        <div id="scanStatusSub" class="text-xs text-gray-500">Perangkat terdeteksi di jaringan</div>
                    </div>
                </div>

                <!-- Detail Info -->
                <div id="scanDetailGrid" class="hidden grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">Hostname</div>
                        <div id="scanHostname" class="font-semibold text-gray-900">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">Response Time</div>
                        <div id="scanResponseTime" class="font-semibold text-gray-900">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">MAC Address</div>
                        <div id="scanMac" class="font-mono text-xs text-gray-900">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">Open Ports</div>
                        <div id="scanPorts" class="font-mono text-xs text-gray-900">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 col-span-2">
                        <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide mb-0.5">Tipe Perangkat</div>
                        <div id="scanDeviceType" class="font-semibold text-gray-900">-</div>
                    </div>
                </div>

                <!-- Vendor Info -->
                <div id="scanVendorSection" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="text-[11px] text-blue-600 font-medium uppercase tracking-wide mb-1">Vendor / Perangkat (IEEE MAC)</div>
                    <div class="flex items-center gap-2">
                        <span id="scanVendor" class="font-semibold text-blue-800 bg-blue-100 px-2 py-0.5 rounded text-xs"></span>
                        <span id="scanProbableDevice" class="text-xs text-gray-600"></span>
                    </div>
                </div>

                <!-- Loading spinner -->
                <div id="scanLoading" class="hidden flex flex-col items-center justify-center py-8">
                    <div class="flex items-center gap-3 text-gray-600 mb-3">
                        <i class="fa-solid fa-spinner fa-spin text-xl text-blue-600"></i>
                        <span class="text-sm font-medium">Memindai <?php echo e($activeSubnet); ?>.<span id="scanLoadingSuffix">X</span>...</span>
                    </div>
                    <p class="text-xs text-gray-400">ICMP Ping + TCP Port Probe + Hostname Resolution</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-100 flex justify-end">
                <button onclick="closeScanModal()" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-full font-medium">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- AI Quick Diagnose Modal -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div id="aiDiagnoseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-indigo-50 via-purple-50 to-blue-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md">
                        <i class="fa-solid fa-robot text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            AI Network Diagnosis
                            <span id="aiModalIpBadge" class="text-xs font-mono font-bold text-indigo-700 bg-indigo-100 px-2.5 py-0.5 rounded-full border border-indigo-200">172.16.250.X</span>
                        </h3>
                        <p class="text-xs text-gray-500">Analisa cerdas status perangkat, vendor MAC, port, & kesesuaian Excel</p>
                    </div>
                </div>
                <button onclick="closeAiDiagnoseModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-full hover:bg-white/80 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <!-- Loading State -->
                <div id="aiModalLoading" class="flex flex-col items-center justify-center py-10 space-y-4">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 animate-spin flex items-center justify-center p-1">
                            <div class="w-full h-full bg-white rounded-full"></div>
                        </div>
                        <i class="fa-solid fa-brain absolute inset-0 m-auto text-indigo-600 text-xl flex items-center justify-center"></i>
                    </div>
                    <div class="text-center">
                        <div class="text-sm font-semibold text-gray-800" id="aiModalLoadingText">Menghubungi AI Agent & Menjalankan Tools...</div>
                        <p class="text-xs text-gray-500 mt-1">AI sedang menginstruksikan worker untuk ping, cek hostname, port, dan verifikasi Excel</p>
                    </div>
                </div>

                <!-- Executed Steps Section -->
                <div id="aiModalStepsCard" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
                    <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-gears text-indigo-600"></i>
                        <span>Langkah Eksekusi Worker (Tools)</span>
                    </div>
                    <div id="aiModalStepsList" class="space-y-1.5 text-xs"></div>
                </div>

                <!-- AI Response Markdown Output -->
                <div id="aiModalResultCard" class="hidden bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                    <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-2 pb-2 border-b border-gray-100">
                        <i class="fa-solid fa-comment-dots text-indigo-600"></i>
                        <span>Hasil Diagnosa & Rekomendasi</span>
                    </div>
                    <div id="aiModalResultText" class="ai-markdown-content text-gray-800"></div>
                </div>

                <!-- Error Box -->
                <div id="aiModalErrorCard" class="hidden bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-xs">
                    <div class="font-bold flex items-center gap-1.5 mb-1 text-sm">
                        <i class="fa-solid fa-triangle-exclamation text-red-500"></i> Gagal Melakukan Diagnosa
                    </div>
                    <p id="aiModalErrorText">Pesan error...</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                <button onclick="copyAiDiagnoseResult()" id="btnCopyAiDiagnose" class="hidden px-3.5 py-1.5 text-xs text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium shadow-sm flex items-center gap-1.5 transition">
                    <i class="fa-regular fa-copy"></i>
                    <span id="btnCopyAiDiagnoseText">Salin Analisa</span>
                </button>
                <div class="ml-auto">
                    <button onclick="closeAiDiagnoseModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 bg-gray-100 rounded-full transition">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- Floating AI Copilot Trigger Button -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="fixed bottom-6 right-6 z-40">
        <button onclick="toggleAiChat(true)" class="group relative flex items-center gap-2.5 px-4 py-3 rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 text-white font-semibold shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 border-2 border-white/20">
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-yellow-400"></span>
            </span>
            <i class="fa-solid fa-robot text-lg group-hover:rotate-12 transition-transform"></i>
            <span class="text-sm tracking-wide">AI Copilot</span>
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- AI Copilot Sliding Chat Drawer -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div id="aiChatBackdrop" onclick="toggleAiChat(false)" class="fixed inset-0 bg-black/40 backdrop-blur-xs hidden z-50 transition-opacity"></div>

    <aside id="aiChatDrawer" class="fixed top-0 right-0 h-full w-full sm:w-[480px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-l border-gray-200">
        <!-- Drawer Header -->
        <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 text-white flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold border border-white/20">
                    <i class="fa-solid fa-robot text-base text-yellow-300"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold tracking-tight">AI Network Copilot</h2>
                    <p class="text-[11px] text-white/80 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-green-400 pulse-dot"></span>
                        <span>Subnet <?php echo e($activeSubnet); ?>.x &bull; Tool-Calling Active</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button onclick="clearAiChatHistory()" class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition text-xs" title="Bersihkan Chat">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
                <button onclick="toggleAiChat(false)" class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Quick Prompts Chips -->
        <div class="px-4 py-2.5 bg-slate-50 border-b border-gray-200 flex items-center gap-2 overflow-x-auto text-[11px] no-scrollbar">
            <span class="text-gray-400 font-medium whitespace-nowrap"><i class="fa-solid fa-wand-magic-sparkles text-indigo-500"></i> Coba:</span>
            <button onclick="sendQuickPrompt('Berikan ringkasan statistik subnet saat ini dan apa saja anomali yang ditemukan.')" class="px-2.5 py-1 bg-white hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-gray-200 rounded-full whitespace-nowrap text-gray-700 font-medium transition shadow-xs">
                📊 Ringkasan Subnet
            </button>
            <button onclick="sendQuickPrompt('Periksa apakah ada IP yang aktif di jaringan tetapi datanya belum terdaftar di file Excel.')" class="px-2.5 py-1 bg-white hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-gray-200 rounded-full whitespace-nowrap text-gray-700 font-medium transition shadow-xs">
                ⚠️ Cek IP Anomali
            </button>
            <button onclick="sendQuickPrompt('Tolong cari data user atau PC dengan kata kunci Printer di file Excel.')" class="px-2.5 py-1 bg-white hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-gray-200 rounded-full whitespace-nowrap text-gray-700 font-medium transition shadow-xs">
                🖨️ Cari Printer
            </button>
        </div>

        <!-- Messages Container -->
        <div id="aiChatMessages" class="flex-1 p-4 overflow-y-auto space-y-4 text-xs">
            <!-- Welcome Assistant Bubble -->
            <div class="flex gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs shrink-0 shadow-xs mt-0.5">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-gray-100 border border-gray-200 rounded-2xl rounded-tl-sm p-3.5 text-gray-800 max-w-[85%] space-y-2">
                    <p class="font-medium text-gray-900">Halo Mas! Saya adalah <strong>AI Network Copilot</strong> Anda.</p>
                    <p class="text-gray-600 leading-relaxed">Saya bisa langsung menjalankan perintah teknis di jaringan lokal ini seperti:</p>
                    <ul class="list-disc pl-4 space-y-1 text-gray-700">
                        <li>Ping IP & cek latensi (ms)</li>
                        <li>Cek Hostname & OS via NetBIOS/DNS</li>
                        <li>Cek MAC address & Vendor hardware</li>
                        <li>Scan port TCP (HTTP, RDP, SMB, Printer)</li>
                        <li>Cari pemilik IP di database Excel</li>
                    </ul>
                    <p class="text-gray-500 italic text-[11px]">Silakan ketik pertanyaan atau perintah di bawah!</p>
                </div>
            </div>
        </div>

        <!-- Typing / Thinking Indicator -->
        <div id="aiChatThinking" class="hidden px-4 py-2 flex items-center gap-2 text-gray-500 text-xs bg-slate-50 border-t border-gray-100">
            <i class="fa-solid fa-spinner fa-spin text-indigo-600"></i>
            <span id="aiChatThinkingText" class="font-medium">AI sedang berpikir & menjalankan tools jaringan...</span>
        </div>

        <!-- Footer Input Area -->
        <div class="p-3 bg-white border-t border-gray-200">
            <form id="aiChatForm" onsubmit="handleAiChatSubmit(event)" class="relative flex items-end gap-2">
                <div class="flex-1 bg-gray-50 border border-gray-300 rounded-xl focus-within:border-indigo-500 focus-within:bg-white transition-all overflow-hidden">
                    <textarea id="aiChatInput" rows="2" placeholder="Tanya AI atau ketik instruksi jaringan... (contoh: cek IP 25)" class="w-full bg-transparent px-3 py-2 text-xs text-gray-900 focus:outline-none resize-none" onkeydown="handleAiChatKeydown(event)"></textarea>
                </div>
                <button type="submit" id="btnSendAiChat" class="h-10 w-10 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white flex items-center justify-center shadow-md transition disabled:opacity-50">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>
            <div class="flex items-center justify-between text-[10px] text-gray-400 mt-1.5 px-1">
                <span>Tekan <kbd class="bg-gray-100 px-1 py-0.5 rounded border border-gray-200 font-mono text-gray-600">Enter</kbd> untuk kirim, <kbd class="bg-gray-100 px-1 py-0.5 rounded border border-gray-200 font-mono text-gray-600">Shift+Enter</kbd> baris baru</span>
            </div>
        </div>
    </aside>

    <!-- Scripts -->
    <script>
        let currentCategoryFilter = 'all';
        let isScanRunning = false;

        // ─── View Mode ───
        function setViewMode(mode) {
            const tableView = document.getElementById('tableViewContainer');
            const gridView = document.getElementById('gridViewContainer');
            const btnTable = document.getElementById('btnViewTable');
            const btnGrid = document.getElementById('btnViewGrid');

            if (mode === 'table') {
                tableView.classList.remove('hidden');
                gridView.classList.add('hidden');
                btnTable.className = "px-3 py-1.5 rounded-md text-xs font-semibold bg-white text-gray-800 shadow-sm flex items-center gap-1.5";
                btnGrid.className = "px-3 py-1.5 rounded-md text-xs font-semibold text-gray-600 hover:text-gray-900 flex items-center gap-1.5";
            } else {
                tableView.classList.add('hidden');
                gridView.classList.remove('hidden');
                btnGrid.className = "px-3 py-1.5 rounded-md text-xs font-semibold bg-white text-gray-800 shadow-sm flex items-center gap-1.5";
                btnTable.className = "px-3 py-1.5 rounded-md text-xs font-semibold text-gray-600 hover:text-gray-900 flex items-center gap-1.5";
            }
        }

        // ─── Filter & Search ───
        function filterIpCards() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const elements = document.querySelectorAll('#ipTableBody tr, #gridViewContainer > div');

            elements.forEach(el => {
                const ip = el.getAttribute('data-ip') || '';
                const machine = el.getAttribute('data-machine') || '';
                const user = el.getAttribute('data-user') || '';
                const hostname = el.getAttribute('data-hostname') || '';
                const category = el.getAttribute('data-category') || '';

                const matchesQuery = ip.includes(query) || machine.includes(query) || user.includes(query) || hostname.includes(query);
                const matchesCategory = (currentCategoryFilter === 'all') || (category === currentCategoryFilter);

                el.classList.toggle('hidden', !(matchesQuery && matchesCategory));
            });
        }

        function setFilterCategory(cat) {
            currentCategoryFilter = cat;
            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.className = "px-3.5 py-1.5 rounded-full bg-gray-100 text-gray-700 font-semibold border border-gray-200 hover:bg-gray-200";
            });
            const activeBtn = document.getElementById(`tab-${cat}`);
            if (activeBtn) {
                activeBtn.className = "px-3.5 py-1.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200";
            }
            filterIpCards();
        }

        // ─── Helper: Update Row MAC & Vendor ───
        function updateRowMacVendor(tr, data) {
            if (!tr) return;
            const macCell = tr.querySelector('.mac-cell');
            const vendorCell = tr.querySelector('.vendor-cell');
            if (macCell && data.mac_address) {
                macCell.textContent = data.mac_address;
            }
            if (vendorCell && data.vendor) {
                vendorCell.innerHTML = `<div class="font-medium text-gray-900"><span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mr-1">${data.vendor}</span></div>` +
                    (data.probable_device ? `<div class="text-[11px] text-gray-500 mt-0.5"><i class="fa-solid fa-microchip text-blue-500 mr-1"></i> ${data.probable_device}</div>` : '');
            }
        }

        // ─── Single IP: Ping ───
        async function pingSingleIp(suffix) {
            const btn = document.querySelector(`#row-btn-ping-${suffix}`);
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }
            try {
                const res = await fetch('<?php echo e(route("ip.scan.ping")); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                    body: JSON.stringify({ ip_suffix: suffix, subnet: '<?php echo e($activeSubnet); ?>' })
                });
                const data = await res.json();
                if (data.success) {
                    const tr = document.getElementById(`row-ip-${suffix}`);
                    if (tr) {
                        const statusCell = tr.querySelector('.status-cell');
                        const responseCell = tr.querySelector('.response-cell');
                        if (statusCell) {
                            statusCell.innerHTML = data.is_active
                                ? '<span class="badge-google inline-flex items-center gap-1.5 bg-green-100 text-green-800"><span class="h-2 w-2 rounded-full bg-green-600 pulse-dot"></span> ONLINE</span>'
                                : '<span class="badge-google bg-gray-100 text-gray-500 font-normal">OFFLINE</span>';
                        }
                        if (responseCell) {
                            responseCell.innerHTML = data.response_time
                                ? `<span class="text-green-700 font-semibold">${data.response_time}</span>`
                                : '<span class="text-gray-400">-</span>';
                        }
                        updateRowMacVendor(tr, data);
                    }
                }
            } catch (e) { console.error('Ping error:', e); }
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-wifi"></i>'; }
        }

        // ─── Single IP: Hostname ───
        async function hostnameSingleIp(suffix) {
            const btn = document.querySelector(`#row-btn-hostname-${suffix}`);
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }
            try {
                const res = await fetch('<?php echo e(route("ip.scan.hostname")); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                    body: JSON.stringify({ ip_suffix: suffix, subnet: '<?php echo e($activeSubnet); ?>' })
                });
                const data = await res.json();
                if (data.success) {
                    const tr = document.getElementById(`row-ip-${suffix}`);
                    if (tr) {
                        const hostnameCell = tr.querySelector('.hostname-cell');
                        const deviceCell = tr.querySelector('.device-cell');
                        // Only update hostname if API returned a value (don't overwrite with '-')
                        if (hostnameCell && data.hostname) {
                            hostnameCell.innerHTML = `<span class="font-mono text-xs text-indigo-700 bg-indigo-50 font-semibold px-2 py-0.5 rounded border border-indigo-100">${data.hostname}</span>`;
                        }
                        if (deviceCell && data.device_type && data.device_type !== 'Unknown') {
                            deviceCell.textContent = data.device_type;
                        }
                    }
                }
            } catch (e) { console.error('Hostname error:', e); }
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-desktop"></i>'; }
        }

        // ─── Single IP: MAC + Vendor ───
        async function macSingleIp(suffix) {
            const btn = document.querySelector(`#row-btn-mac-${suffix}`);
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }
            try {
                const res = await fetch('<?php echo e(route("ip.scan.mac")); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                    body: JSON.stringify({ ip_suffix: suffix, subnet: '<?php echo e($activeSubnet); ?>' })
                });
                const data = await res.json();
                if (data.success) {
                    const tr = document.getElementById(`row-ip-${suffix}`);
                    if (tr) {
                        const macCell = tr.querySelector('.mac-cell');
                        const vendorCell = tr.querySelector('.vendor-cell');
                        if (macCell) macCell.textContent = data.mac_address || '-';
                        if (vendorCell) {
                            if (data.vendor) {
                                vendorCell.innerHTML = `<div class="font-medium text-gray-900"><span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mr-1">${data.vendor}</span></div>` +
                                    (data.probable_device ? `<div class="text-[11px] text-gray-500 mt-0.5"><i class="fa-solid fa-microchip text-blue-500 mr-1"></i> ${data.probable_device}</div>` : '');
                            } else if (data.mac_address) {
                                vendorCell.innerHTML = '<span class="text-gray-400 text-[11px] font-mono">Perangkat Jaringan</span>';
                            } else {
                                vendorCell.innerHTML = '<span class="text-gray-400 italic">-</span>';
                            }
                        }
                    }
                }
            } catch (e) { console.error('MAC error:', e); }
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-network-wired"></i>'; }
        }

        // ─── Batch Scan: Ping Only ───
        let isBatchPingRunning = false;
        async function startBatchPing(start, end) {
            if (isBatchPingRunning) return;
            isBatchPingRunning = true;
            const total = end - start + 1;
            let scanned = 0;
            let totalOnline = 0;
            const progressContainer = document.getElementById('scanProgressContainer');
            const progressBar = document.getElementById('scanProgressBar');
            const statusText = document.getElementById('scanStatusText');
            const progressDetail = document.getElementById('scanProgressDetail');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '2%';
            statusText.innerText = 'Memulai ping...';
            progressDetail.innerText = '';
            const CHUNK = 8;
            try {
                for (let i = start; i <= end; i += CHUNK) {
                    const chunkEnd = Math.min(i + CHUNK - 1, end);
                    const promises = [];
                    for (let j = i; j <= chunkEnd; j++) {
                        promises.push(fetch('<?php echo e(route("ip.scan.ping")); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                            body: JSON.stringify({ ip_suffix: j, subnet: '<?php echo e($activeSubnet); ?>' })
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                if (data.is_active) totalOnline++;
                                const tr = document.getElementById(`row-ip-${j}`);
                                if (tr) {
                                    const statusCell = tr.querySelector('.status-cell');
                                    const responseCell = tr.querySelector('.response-cell');
                                    if (statusCell) statusCell.innerHTML = data.is_active
                                        ? '<span class="badge-google bg-green-100 text-green-700">ONLINE</span>'
                                        : '<span class="badge-google bg-gray-100 text-gray-500">OFFLINE</span>';
                                    if (responseCell) responseCell.textContent = data.response_time || '-';
                                    updateRowMacVendor(tr, data);
                                    tr.classList.remove('row-online', 'row-offline');
                                    tr.classList.add(data.is_active ? 'row-online' : 'row-offline');
                                }
                            }
                        }).catch(() => null));
                    }
                    await Promise.all(promises);
                    scanned += (chunkEnd - i + 1);
                    const pct = Math.round((scanned / total) * 100);
                    progressBar.style.width = `${Math.min(pct, 99)}%`;
                    statusText.innerText = `${pct}% — ${scanned}/${total} IP (${totalOnline} Online)`;
                    progressDetail.innerText = `<?php echo e($activeSubnet); ?>.${i} - .${chunkEnd}`;
                }
                progressBar.style.width = '100%';
                statusText.innerText = `Ping selesai! ${total} IP dicek, ${totalOnline} aktif.`;
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                statusText.innerText = 'Error: ' + e.message;
                progressBar.classList.add('bg-red-500');
            } finally {
                isBatchPingRunning = false;
            }
        }

        // ─── Batch Scan: Hostname Only ───
        let isBatchHostnameRunning = false;
        async function startBatchHostname(start, end) {
            if (isBatchHostnameRunning) return;
            isBatchHostnameRunning = true;
            const total = end - start + 1;
            let scanned = 0;
            const progressContainer = document.getElementById('scanProgressContainer');
            const progressBar = document.getElementById('scanProgressBar');
            const statusText = document.getElementById('scanStatusText');
            const progressDetail = document.getElementById('scanProgressDetail');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '2%';
            statusText.innerText = 'Memulai hostname scan...';
            progressDetail.innerText = '';
            const CHUNK = 3;
            try {
                for (let i = start; i <= end; i += CHUNK) {
                    const chunkEnd = Math.min(i + CHUNK - 1, end);
                    const promises = [];
                    for (let j = i; j <= chunkEnd; j++) {
                        promises.push(fetch('<?php echo e(route("ip.scan.hostname")); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                            body: JSON.stringify({ ip_suffix: j, subnet: '<?php echo e($activeSubnet); ?>' })
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                const tr = document.getElementById(`row-ip-${j}`);
                                if (tr) {
                                    const hostnameCell = tr.querySelector('.hostname-cell');
                                    const deviceCell = tr.querySelector('.device-cell');
                                    if (hostnameCell) hostnameCell.textContent = data.hostname || '-';
                                    if (deviceCell) deviceCell.textContent = data.device_type || '-';
                                }
                            }
                        }).catch(() => null));
                    }
                    await Promise.all(promises);
                    scanned += (chunkEnd - i + 1);
                    const pct = Math.round((scanned / total) * 100);
                    progressBar.style.width = `${Math.min(pct, 99)}%`;
                    statusText.innerText = `${pct}% — ${scanned}/${total} IP`;
                    progressDetail.innerText = `<?php echo e($activeSubnet); ?>.${i} - .${chunkEnd}`;
                }
                progressBar.style.width = '100%';
                statusText.innerText = `Hostname scan selesai! ${total} IP dicek.`;
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                statusText.innerText = 'Error: ' + e.message;
                progressBar.classList.add('bg-red-500');
            } finally {
                isBatchHostnameRunning = false;
            }
        }

        // ─── Batch Scan: MAC Only ───
        let isBatchMacRunning = false;
        async function startBatchMac(start, end) {
            if (isBatchMacRunning) return;
            isBatchMacRunning = true;
            const total = end - start + 1;
            let scanned = 0;
            const progressContainer = document.getElementById('scanProgressContainer');
            const progressBar = document.getElementById('scanProgressBar');
            const statusText = document.getElementById('scanStatusText');
            const progressDetail = document.getElementById('scanProgressDetail');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '2%';
            statusText.innerText = 'Memulai MAC scan...';
            progressDetail.innerText = '';
            const CHUNK = 10;
            try {
                for (let i = start; i <= end; i += CHUNK) {
                    const chunkEnd = Math.min(i + CHUNK - 1, end);
                    const promises = [];
                    for (let j = i; j <= chunkEnd; j++) {
                        promises.push(fetch('<?php echo e(route("ip.scan.mac")); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json' },
                            body: JSON.stringify({ ip_suffix: j, subnet: '<?php echo e($activeSubnet); ?>' })
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                const tr = document.getElementById(`row-ip-${j}`);
                                if (tr) {
                                    const macCell = tr.querySelector('.mac-cell');
                                    const vendorCell = tr.querySelector('.vendor-cell');
                                    if (macCell) macCell.textContent = data.mac_address || '-';
                                    if (vendorCell) vendorCell.textContent = data.vendor ? `${data.vendor}${data.probable_device ? ' ('+data.probable_device+')' : ''}` : '-';
                                }
                            }
                        }).catch(() => null));
                    }
                    await Promise.all(promises);
                    scanned += (chunkEnd - i + 1);
                    const pct = Math.round((scanned / total) * 100);
                    progressBar.style.width = `${Math.min(pct, 99)}%`;
                    statusText.innerText = `${pct}% — ${scanned}/${total} IP`;
                    progressDetail.innerText = `<?php echo e($activeSubnet); ?>.${i} - .${chunkEnd}`;
                }
                progressBar.style.width = '100%';
                statusText.innerText = `MAC scan selesai! ${total} IP dicek.`;
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                statusText.innerText = 'Error: ' + e.message;
                progressBar.classList.add('bg-red-500');
            } finally {
                isBatchMacRunning = false;
            }
        }

        // ─── Batch Network Scan ───
        async function startBatchScan(start, end) {
            if (isScanRunning) return;
            isScanRunning = true;

            const btn = document.getElementById('btnFullScan');
            const btnIcon = document.getElementById('btnFullScanIcon');
            const btnText = document.getElementById('btnFullScanText');
            const progressContainer = document.getElementById('scanProgressContainer');
            const progressBar = document.getElementById('scanProgressBar');
            const statusText = document.getElementById('scanStatusText');
            const progressDetail = document.getElementById('scanProgressDetail');

            // Disable scan button
            btn.disabled = true;
            btnIcon.className = "fa-solid fa-spinner fa-spin";
            btnText.textContent = "Scanning...";

            progressContainer.classList.remove('hidden');
            progressBar.style.width = '2%';
            statusText.innerText = 'Memulai scan...';
            progressDetail.innerText = '';

            try {
                // Scan in tiny chunks (5 IPs each) for smooth gradual progress
                const chunkSize = 5;
                const totalIps = end - start + 1;
                const totalChunks = Math.ceil(totalIps / chunkSize);
                let totalActive = 0;
                let scannedCount = 0;

                for (let ci = 0; ci < totalChunks; ci++) {
                    const chunkStart = start + (ci * chunkSize);
                    const chunkEnd = Math.min(chunkStart + chunkSize - 1, end);
                    const chunkIps = chunkEnd - chunkStart + 1;

                    const res = await fetch('/scan/range', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ start: chunkStart, end: chunkEnd, subnet: '<?php echo e($activeSubnet); ?>' })
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: HTTP ${res.status}`);
                    }

                    const data = await res.json();
                    totalActive += (data.active_count || 0);
                    scannedCount += chunkIps;

                    // Update progress after each chunk
                    const pct = Math.round((scannedCount / totalIps) * 100);
                    progressBar.style.width = `${Math.min(pct, 99)}%`;
                    statusText.innerText = `${pct}% — ${scannedCount}/${totalIps} IP`;
                    progressDetail.innerText = `<?php echo e($activeSubnet); ?>.${chunkStart} - .${chunkEnd} | ${totalActive} aktif`;
                }

                // Final update
                progressBar.style.width = '100%';
                statusText.innerText = `Selesai! ${totalActive} perangkat aktif dari ${totalIps} IP.`;
                progressDetail.innerText = 'Memuat ulang halaman...';

                setTimeout(() => location.reload(), 600);
            } catch (err) {
                console.error('Batch scan error:', err);
                statusText.innerText = 'Error: ' + err.message;
                progressDetail.innerText = 'Silakan coba lagi.';
                progressBar.classList.add('bg-red-500');
                progressBar.classList.remove('bg-blue-600');

                // Re-enable button
                btn.disabled = false;
                btnIcon.className = "fa-solid fa-arrows-rotate";
                btnText.textContent = "Scan Jaringan (1-254)";
                isScanRunning = false;
            }
        }

        // ─── Edit Modal ───
        function openEditModal(suffix, machine, user, windows) {
            document.getElementById('modalIpSuffix').value = suffix;
            document.getElementById('modalIpDisplay').innerText = `<?php echo e($activeSubnet); ?>.${suffix}`;
            document.getElementById('modalMachine').value = machine;
            document.getElementById('modalUser').value = user;
            document.getElementById('modalWindows').value = windows;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        async function saveExcelData(e) {
            e.preventDefault();
            const suffix = document.getElementById('modalIpSuffix').value;
            const machine = document.getElementById('modalMachine').value;
            const user = document.getElementById('modalUser').value;
            const windows = document.getElementById('modalWindows').value;

            try {
                const res = await fetch('/excel/update', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ip_suffix: suffix, machine, user, windows, subnet: '<?php echo e($activeSubnet); ?>' })
                });

                const data = await res.json();
                if (data.success) {
                    closeEditModal();

                    // Update table row directly
                    const tr = document.querySelector(`#ipTableBody tr[data-suffix="${suffix}"]`);
                    if (tr) {
                        tr.setAttribute('data-machine', machine.toLowerCase());
                        tr.setAttribute('data-user', user.toLowerCase());

                        const cells = tr.querySelectorAll('td');
                        if (cells.length >= 6) {
                            cells[3].innerHTML = `<span class="font-medium ${machine ? 'text-gray-900' : 'text-gray-400 italic'}">${machine || '(Kosong)'}</span>`;
                            cells[4].innerHTML = `<span class="font-medium ${user ? 'text-gray-800' : 'text-gray-400 italic'}">${user || '(Kosong)'}</span>`;
                            cells[5].innerText = windows || '-';
                        }

                        // Update category
                        const isOnline = tr.querySelector('.bg-green-600') !== null;
                        const hasExcel = machine !== '' || user !== '';
                        if (isOnline && hasExcel) tr.dataset.category = 'active_matched';
                        else if (isOnline && !hasExcel) tr.dataset.category = 'active_unmapped';
                        else if (!isOnline && hasExcel) tr.dataset.category = 'offline_mapped';
                        else tr.dataset.category = 'free_ip';
                    }

                    // Update grid card
                    const gridCard = document.querySelector(`#gridViewContainer > div[data-suffix="${suffix}"]`);
                    if (gridCard) {
                        gridCard.setAttribute('data-machine', machine.toLowerCase());
                        gridCard.setAttribute('data-user', user.toLowerCase());
                    }

                    filterIpCards();
                } else {
                    alert('Gagal mengupdate file Excel.');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        // ─── Single IP Scan ───
        async function scanSingleIp(suffix) {
            const modal = document.getElementById('scanModal');
            const loading = document.getElementById('scanLoading');
            const header = document.getElementById('scanModalHeader');
            const banner = document.getElementById('scanStatusBanner');
            const icon = document.getElementById('scanStatusIcon');
            const statusText = document.getElementById('scanModalStatusText');
            const statusSub = document.getElementById('scanStatusSub');
            const detailGrid = document.getElementById('scanDetailGrid');
            const vendorSection = document.getElementById('scanVendorSection');

            // Reset & show modal
            document.getElementById('scanModalIp').innerText = `172.16.250.${suffix}`;
            document.getElementById('scanLoadingSuffix').innerText = suffix;
            loading.classList.remove('hidden');
            banner.classList.add('hidden');
            detailGrid.classList.add('hidden');
            vendorSection.classList.add('hidden');
            header.className = 'px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50';

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s

                const res = await fetch('/scan/ip', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ip_suffix: suffix }),
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await res.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned non-JSON (HTTP ' + res.status + ')');
                }

                const data = await res.json();

                loading.classList.add('hidden');

                if (!data.success) {
                    alert('Gagal memindai IP: ' + (data.message || 'Unknown error'));
                    closeScanModal();
                    return;
                }

                const isOnline = data.is_active;

                // Header
                header.className = isOnline
                    ? 'px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-green-50'
                    : 'px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50';

                // Banner
                banner.classList.remove('hidden');
                banner.className = isOnline
                    ? 'flex items-center gap-3 p-3 rounded-xl border border-green-200 bg-green-50'
                    : 'flex items-center gap-3 p-3 rounded-xl border border-gray-200 bg-gray-50';
                icon.className = isOnline
                    ? 'w-10 h-10 rounded-full flex items-center justify-center text-white bg-green-600'
                    : 'w-10 h-10 rounded-full flex items-center justify-center text-white bg-gray-400';
                icon.innerHTML = isOnline
                    ? '<i class="fa-solid fa-check text-lg"></i>'
                    : '<i class="fa-solid fa-xmark text-lg"></i>';
                statusText.className = isOnline ? 'text-sm font-bold text-green-800' : 'text-sm font-bold text-gray-600';
                statusText.innerText = isOnline ? 'ONLINE' : 'OFFLINE';
                statusSub.innerText = isOnline ? 'Perangkat terdeteksi & merespon' : 'Tidak ada respons dari perangkat';

                // Details
                detailGrid.classList.remove('hidden');
                document.getElementById('scanHostname').innerText = data.hostname || '-';
                document.getElementById('scanResponseTime').innerText = data.response_time || '-';
                document.getElementById('scanMac').innerText = data.mac_address || '-';
                document.getElementById('scanPorts').innerText = data.open_ports?.length ? data.open_ports.join(', ') : '-';
                document.getElementById('scanDeviceType').innerText = data.device_type || '-';

                // Vendor
                if (data.vendor) {
                    vendorSection.classList.remove('hidden');
                    document.getElementById('scanVendor').innerText = data.vendor;
                    document.getElementById('scanProbableDevice').innerText = data.probable_device || '';
                }

                // ── Update Table Row ──
                updateTableRow(suffix, data);

                // ── Update Grid Card ──
                updateGridCard(suffix, data);

            } catch (err) {
                loading.classList.add('hidden');
                if (err.name === 'AbortError') {
                    alert('Scan timeout — perangkat tidak merespon dalam 30 detik.');
                } else {
                    alert('Error saat scan: ' + err.message);
                }
                closeScanModal();
            }
        }

        function updateTableRow(suffix, data) {
            const tr = document.querySelector(`#ipTableBody tr[data-suffix="${suffix}"]`);
            if (!tr) return;

            const isOnline = data.is_active;

            // Status cell
            const statusCell = document.getElementById('status-cell-' + suffix);
            if (statusCell) {
                statusCell.innerHTML = isOnline
                    ? '<span class="badge-google inline-flex items-center gap-1.5 bg-green-100 text-green-800"><span class="h-2 w-2 rounded-full bg-green-600 pulse-dot"></span> ONLINE</span>'
                    : '<span class="badge-google bg-gray-100 text-gray-500 font-normal">OFFLINE</span>';
            }

            // Hostname
            const hostCell = document.getElementById('hostname-cell-' + suffix);
            if (hostCell) {
                const hn = data.hostname || '-';
                hostCell.innerHTML = `<span class="font-mono text-xs ${data.hostname ? 'text-indigo-700 bg-indigo-50 font-semibold px-2 py-0.5 rounded border border-indigo-100' : 'text-gray-400 italic'}">${hn}</span>`;
                tr.setAttribute('data-hostname', (data.hostname || '').toLowerCase());
            }

            // MAC
            const macCell = document.getElementById('mac-cell-' + suffix);
            if (macCell) macCell.textContent = data.mac_address || '-';

            // Response time
            const respCell = document.getElementById('resp-cell-' + suffix);
            if (respCell) {
                respCell.innerHTML = data.response_time
                    ? `<span class="text-green-700 font-semibold">${data.response_time}</span>`
                    : '<span class="text-gray-400">-</span>';
            }

            // Vendor
            const vendorCell = document.getElementById('vendor-cell-' + suffix);
            if (vendorCell) {
                if (data.vendor) {
                    vendorCell.innerHTML = `<div class="font-medium text-gray-900"><span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mr-1">${data.vendor}</span></div><div class="text-[11px] text-gray-500 mt-0.5"><i class="fa-solid fa-microchip text-blue-500 mr-1"></i> ${data.probable_device || ''}</div>`;
                } else if (data.mac_address) {
                    vendorCell.innerHTML = `<span class="text-gray-400 text-[11px] font-mono">Perangkat Jaringan</span>`;
                } else {
                    vendorCell.innerHTML = '<span class="text-gray-400 italic">-</span>';
                }
            }

            // Category
            const excelMachine = tr.getAttribute('data-machine') || '';
            const excelUser = tr.getAttribute('data-user') || '';
            const hasExcel = excelMachine !== '' || excelUser !== '';
            if (isOnline && hasExcel) tr.dataset.category = 'active_matched';
            else if (isOnline && !hasExcel) tr.dataset.category = 'active_unmapped';
            else if (!isOnline && hasExcel) tr.dataset.category = 'offline_mapped';
            else tr.dataset.category = 'free_ip';
        }

        function updateGridCard(suffix, data) {
            const card = document.querySelector(`#gridViewContainer .google-card[data-suffix="${suffix}"]`);
            if (!card) return;

            const isOnline = data.is_active;
            const badge = card.querySelector('.badge-google');
            if (badge) {
                if (isOnline) {
                    badge.className = 'badge-google bg-green-100 text-green-800 inline-flex items-center gap-1';
                    badge.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-green-600 pulse-dot"></span> ONLINE';
                } else {
                    badge.className = 'badge-google bg-gray-100 text-gray-500 font-normal';
                    badge.textContent = 'OFFLINE';
                }
            }
        }

        function closeScanModal() {
            const modal = document.getElementById('scanModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // ─── Auto-resolve MAC Vendors on page load ───
        document.addEventListener('DOMContentLoaded', () => {
            const unmappedElements = document.querySelectorAll('[data-mac]');
            unmappedElements.forEach(async (el) => {
                const mac = el.getAttribute('data-mac');
                const suffix = el.getAttribute('data-suffix');
                if (!mac) return;

                const prefix = mac.split(':').slice(0, 3).join(':');
                try {
                    const res = await fetch(`https://api.macvendors.com/${encodeURIComponent(prefix)}`);
                    if (res.ok) {
                        const vendor = await res.text();
                        if (vendor && !vendor.includes('error') && !vendor.includes('not found')) {
                            const cell = document.getElementById(`vendor-cell-${suffix}`);
                            if (cell) {
                                cell.innerHTML = `
                                    <div class="font-medium text-gray-900">
                                        <span class="font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200 mr-1">${vendor}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        <i class="fa-solid fa-microchip text-blue-500 mr-1"></i> Perangkat Jaringan (${vendor})
                                    </div>
                                `;
                            }
                        }
                    }
                } catch (e) {
                    // Ignore
                }
            });

            // Restore search/filter state from sessionStorage
            const savedSearch = sessionStorage.getItem('ip_search_query');
            const savedFilter = sessionStorage.getItem('ip_filter_category');
            if (savedSearch) {
                document.getElementById('searchInput').value = savedSearch;
                sessionStorage.removeItem('ip_search_query');
            }
            if (savedFilter) {
                setFilterCategory(savedFilter);
                sessionStorage.removeItem('ip_filter_category');
            } else if (savedSearch) {
                filterIpCards();
            }
        });

        // ═══════════════════════════════════════════════════════
        // AI NETWORK AGENT JAVASCRIPT LOGIC
        // ═══════════════════════════════════════════════════════
        const activeSubnet = '<?php echo e($activeSubnet); ?>';
        let aiConversationHistory = [];
        let isAiDiagnoseRunning = false;
        let isAiChatRunning = false;
        let lastDiagnoseMarkdown = '';

        // Safe markdown parser
        function parseMarkdown(md) {
            if (typeof marked !== 'undefined' && marked.parse) {
                return marked.parse(md);
            }
            // Basic fallback if marked.js not loaded
            return md.replace(/\n/g, '<br>');
        }

        // ─── AI Quick Diagnose ───
        async function diagnoseWithAi(suffix) {
            if (isAiDiagnoseRunning) return;
            isAiDiagnoseRunning = true;

            const modal = document.getElementById('aiDiagnoseModal');
            const ipBadge = document.getElementById('aiModalIpBadge');
            const loading = document.getElementById('aiModalLoading');
            const loadingText = document.getElementById('aiModalLoadingText');
            const stepsCard = document.getElementById('aiModalStepsCard');
            const stepsList = document.getElementById('aiModalStepsList');
            const resultCard = document.getElementById('aiModalResultCard');
            const resultText = document.getElementById('aiModalResultText');
            const errorCard = document.getElementById('aiModalErrorCard');
            const errorText = document.getElementById('aiModalErrorText');
            const btnCopy = document.getElementById('btnCopyAiDiagnose');

            const fullIp = `${activeSubnet}.${suffix}`;
            ipBadge.textContent = fullIp;

            // Reset modal state
            loading.classList.remove('hidden');
            loadingText.textContent = `Menghubungi AI Agent & Menganalisa IP ${fullIp}...`;
            stepsCard.classList.add('hidden');
            stepsList.innerHTML = '';
            resultCard.classList.add('hidden');
            resultText.innerHTML = '';
            errorCard.classList.add('hidden');
            btnCopy.classList.add('hidden');
            lastDiagnoseMarkdown = '';

            // Open modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Button spin animation
            const rowBtn = document.getElementById(`row-btn-ai-${suffix}`);
            const gridBtn = document.getElementById(`grid-btn-ai-${suffix}`);
            if (rowBtn) rowBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            if (gridBtn) gridBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/ai/diagnose', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        ip: fullIp,
                        subnet: activeSubnet,
                    }),
                });

                const data = await response.json();
                loading.classList.add('hidden');

                if (data.success) {
                    // Display executed tool steps
                    if (data.steps && data.steps.length > 0) {
                        stepsCard.classList.remove('hidden');
                        stepsList.innerHTML = data.steps.map(s => `
                            <div class="flex items-start gap-2 text-slate-700 bg-white p-2 rounded-lg border border-slate-100 shadow-2xs">
                                <span class="text-indigo-600 font-mono text-[11px] font-semibold">⚡ [${s.tool}]</span>
                                <span class="text-slate-600 flex-1">${s.summary}</span>
                            </div>
                        `).join('');
                    }

                    // Display AI Markdown message
                    resultCard.classList.remove('hidden');
                    lastDiagnoseMarkdown = data.message;
                    resultText.innerHTML = parseMarkdown(data.message);
                    btnCopy.classList.remove('hidden');
                } else {
                    errorCard.classList.remove('hidden');
                    errorText.textContent = data.message || 'Terjadi kegagalan saat menjalankan diagnosa.';
                }
            } catch (err) {
                loading.classList.add('hidden');
                errorCard.classList.remove('hidden');
                errorText.textContent = 'Gagal menghubungi server: ' + err.message;
            } finally {
                isAiDiagnoseRunning = false;
                if (rowBtn) rowBtn.innerHTML = '<i class="fa-solid fa-robot"></i>';
                if (gridBtn) gridBtn.innerHTML = '<i class="fa-solid fa-robot"></i>';
            }
        }

        function closeAiDiagnoseModal() {
            const modal = document.getElementById('aiDiagnoseModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function copyAiDiagnoseResult() {
            if (!lastDiagnoseMarkdown) return;
            navigator.clipboard.writeText(lastDiagnoseMarkdown).then(() => {
                const btnText = document.getElementById('btnCopyAiDiagnoseText');
                const orig = btnText.textContent;
                btnText.textContent = 'Tersalin!';
                setTimeout(() => { btnText.textContent = orig; }, 2000);
            });
        }

        // ─── AI Copilot Sliding Drawer ───
        function toggleAiChat(open) {
            const drawer = document.getElementById('aiChatDrawer');
            const backdrop = document.getElementById('aiChatBackdrop');
            if (open) {
                backdrop.classList.remove('hidden');
                drawer.classList.remove('translate-x-full');
                setTimeout(() => {
                    document.getElementById('aiChatInput').focus();
                }, 300);
            } else {
                backdrop.classList.add('hidden');
                drawer.classList.add('translate-x-full');
            }
        }

        function clearAiChatHistory() {
            aiConversationHistory = [];
            const container = document.getElementById('aiChatMessages');
            container.innerHTML = `
                <div class="flex gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs shrink-0 shadow-xs mt-0.5">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="bg-gray-100 border border-gray-200 rounded-2xl rounded-tl-sm p-3.5 text-gray-800 max-w-[85%] space-y-2">
                        <p class="font-medium text-gray-900">Riwayat percakapan telah dibersihkan.</p>
                        <p class="text-gray-600 text-xs">Silakan ketik pertanyaan atau perintah jaringan baru.</p>
                    </div>
                </div>
            `;
        }

        function sendQuickPrompt(promptText) {
            const input = document.getElementById('aiChatInput');
            input.value = promptText;
            handleAiChatSubmit(new Event('submit'));
        }

        function handleAiChatKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleAiChatSubmit(e);
            }
        }

        async function handleAiChatSubmit(e) {
            if (e && e.preventDefault) e.preventDefault();
            if (isAiChatRunning) return;

            const input = document.getElementById('aiChatInput');
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            isAiChatRunning = true;

            const container = document.getElementById('aiChatMessages');
            const thinking = document.getElementById('aiChatThinking');
            const btnSend = document.getElementById('btnSendAiChat');
            btnSend.disabled = true;

            // 1. Append User Message Bubble
            const userBubble = document.createElement('div');
            userBubble.className = 'flex justify-end gap-2.5';
            userBubble.innerHTML = `
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl rounded-tr-sm p-3 max-w-[85%] shadow-xs break-words">
                    <p class="leading-relaxed">${message.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')}</p>
                </div>
            `;
            container.appendChild(userBubble);
            container.scrollTop = container.scrollHeight;

            // Show thinking indicator
            thinking.classList.remove('hidden');

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        message: message,
                        history: aiConversationHistory,
                        subnet: activeSubnet,
                    }),
                });

                const data = await response.json();
                thinking.classList.add('hidden');

                // Append assistant reply bubble
                const aiBubble = document.createElement('div');
                aiBubble.className = 'flex gap-2.5';

                let toolStepsHtml = '';
                if (data.steps && data.steps.length > 0) {
                    toolStepsHtml = `
                        <div class="mb-2 p-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px] space-y-1">
                            <div class="font-bold text-slate-600 flex items-center gap-1">
                                <i class="fa-solid fa-gears text-indigo-600"></i> ${data.steps.length} Tools Dijalankan:
                            </div>
                            ${data.steps.map(s => `<div class="text-slate-500 font-mono text-[10px]">&bull; ${s.summary}</div>`).join('')}
                        </div>
                    `;
                }

                if (data.success) {
                    aiBubble.innerHTML = `
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center text-xs shrink-0 shadow-xs mt-0.5">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm p-3.5 text-gray-800 max-w-[85%] shadow-xs space-y-2">
                            ${toolStepsHtml}
                            <div class="ai-markdown-content">${parseMarkdown(data.message)}</div>
                        </div>
                    `;

                    // Update memory
                    aiConversationHistory.push({ role: 'user', content: message });
                    aiConversationHistory.push({ role: 'assistant', content: data.message });
                    // Limit history to last 10 messages
                    if (aiConversationHistory.length > 10) {
                        aiConversationHistory = aiConversationHistory.slice(-10);
                    }
                } else {
                    aiBubble.innerHTML = `
                        <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs shrink-0 shadow-xs mt-0.5">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-sm p-3 text-red-700 max-w-[85%] shadow-xs text-xs space-y-1">
                            <div class="font-bold">Gagal memproses pesan</div>
                            <p>${data.message || 'Error tidak diketahui'}</p>
                        </div>
                    `;
                }

                container.appendChild(aiBubble);
                container.scrollTop = container.scrollHeight;
            } catch (err) {
                thinking.classList.add('hidden');
                const errBubble = document.createElement('div');
                errBubble.className = 'flex gap-2.5';
                errBubble.innerHTML = `
                    <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs shrink-0 shadow-xs mt-0.5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-sm p-3 text-red-700 max-w-[85%] shadow-xs text-xs">
                        Koneksi terputus: ${err.message}
                    </div>
                `;
                container.appendChild(errBubble);
                container.scrollTop = container.scrollHeight;
            } finally {
                isAiChatRunning = false;
                btnSend.disabled = false;
                input.focus();
            }
        }
    </script>
</body>
</html><?php /**PATH C:\laragon\www\app-ip-tracer\resources\views/ip_tracing/dashboard.blade.php ENDPATH**/ ?>