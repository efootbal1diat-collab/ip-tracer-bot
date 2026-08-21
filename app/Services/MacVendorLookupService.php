<?php

namespace App\Services;

class MacVendorLookupService
{
    /**
     * OUI Prefix Table (First 3 bytes of MAC) — 100% In-Memory Offline Database
     */
    protected static array $ouiDatabase = [
        // HP / Hewlett Packard
        '34:5A:60' => ['vendor' => 'HP Inc.', 'device' => 'PC Desktop / Workstation HP'],
        'F8:75:A4' => ['vendor' => 'HP Inc.', 'device' => 'PC / Printer HP'],
        '00:1E:0B' => ['vendor' => 'HP Inc.', 'device' => 'PC / Laptop HP'],
        '00:25:B3' => ['vendor' => 'HP Inc.', 'device' => 'PC / Laptop HP'],
        '3C:D9:2B' => ['vendor' => 'HP Inc.', 'device' => 'PC / Laptop HP'],
        'B4:B5:2F' => ['vendor' => 'HP Inc.', 'device' => 'HP Network Printer / Scanner'],
        '10:60:4B' => ['vendor' => 'HP Inc.', 'device' => 'HP Network Printer'],
        '2C:44:FD' => ['vendor' => 'HP Inc.', 'device' => 'HP ProLiant Server / PC'],

        // Dell
        '4C:D5:77' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Server Dell'],
        'A4:BB:6D' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Laptop Dell'],
        '90:B1:1C' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Laptop Dell'],
        '18:66:DA' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Laptop Dell OptiPlex/Latitude'],
        '74:86:7A' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Laptop Dell'],
        'D4:BE:D9' => ['vendor' => 'Dell Inc.', 'device' => 'Dell PowerEdge Server / PC'],
        'B8:2A:72' => ['vendor' => 'Dell Inc.', 'device' => 'PC / Laptop Dell'],

        // Lenovo
        'DC:45:46' => ['vendor' => 'Lenovo', 'device' => 'PC / Laptop Lenovo ThinkPad'],
        '54:EE:75' => ['vendor' => 'Lenovo', 'device' => 'PC / Laptop Lenovo ThinkCentre'],
        'E8:6A:64' => ['vendor' => 'Lenovo', 'device' => 'PC / Laptop Lenovo'],
        '70:72:0D' => ['vendor' => 'Lenovo', 'device' => 'PC / Laptop Lenovo ThinkPad'],
        '00:21:CC' => ['vendor' => 'Lenovo', 'device' => 'ThinkPad Laptop'],

        // Intel (Motherboards / NICs)
        '48:21:0B' => ['vendor' => 'Intel Corporate', 'device' => 'PC / Laptop (Intel NIC)'],
        '00:1E:67' => ['vendor' => 'Intel Corporation', 'device' => 'PC / Server Intel'],
        '8C:85:90' => ['vendor' => 'Intel Corporate', 'device' => 'Intel Wi-Fi / LAN Card'],
        'B4:96:91' => ['vendor' => 'Intel Corporate', 'device' => 'Intel Wireless / Ethernet'],
        '3C:F0:11' => ['vendor' => 'Intel Corporate', 'device' => 'PC Desktop Intel'],

        // Realtek (Motherboards / USB LAN)
        '60:FF:9E' => ['vendor' => 'Realtek Semiconductor', 'device' => 'PC / Laptop Ethernet Card'],
        '00:E0:4C' => ['vendor' => 'Realtek Semiconductor', 'device' => 'PC Network Card Realtek'],
        '48:5B:39' => ['vendor' => 'Realtek Semiconductor', 'device' => 'Realtek PCIe GBE Controller'],
        '2C:4D:54' => ['vendor' => 'Realtek Semiconductor', 'device' => 'Realtek LAN Card'],

        // ASUS / ASRock / MSI / Gigabyte
        'D0:39:EA' => ['vendor' => 'ASUSTeK Computer Inc.', 'device' => 'PC / Laptop ASUS'],
        'F4:6D:04' => ['vendor' => 'ASUSTeK Computer', 'device' => 'PC / Laptop ASUS'],
        '04:D4:C4' => ['vendor' => 'ASUSTeK Computer', 'device' => 'ASUS Motherboard / Laptop'],
        'B4:2E:99' => ['vendor' => 'Gigabyte Technology', 'device' => 'PC Desktop Gigabyte Motherboard'],
        'E0:D5:5E' => ['vendor' => 'Gigabyte Technology', 'device' => 'PC Desktop Gigabyte'],
        '70:85:C2' => ['vendor' => 'ASRock Incorporation', 'device' => 'PC Desktop ASRock Motherboard'],
        '2C:F0:5D' => ['vendor' => 'Micro-Star Int\'l (MSI)', 'device' => 'PC Desktop MSI Motherboard'],
        'D4:5D:64' => ['vendor' => 'Micro-Star Int\'l (MSI)', 'device' => 'PC Desktop MSI'],

        // Cisco / Fortinet / Mikrotik / Ubiquiti / Ruijie / Reyee
        '18:C0:4D' => ['vendor' => 'Cisco Systems, Inc.', 'device' => 'Switch / Network Equipment Cisco'],
        '48:74:10' => ['vendor' => 'Cisco Systems', 'device' => 'Network Equipment Cisco Catalyst'],
        '00:1D:71' => ['vendor' => 'Cisco Systems', 'device' => 'Cisco Switch / AP'],
        'E4:3D:1A' => ['vendor' => 'Fortinet Technologies', 'device' => 'Fortigate Firewall / Gateway'],
        '00:09:0F' => ['vendor' => 'Fortinet Technologies', 'device' => 'FortiGate Security Gateway'],
        '48:8F:5A' => ['vendor' => 'MikroTik', 'device' => 'MikroTik RouterBOARD / Router'],
        '6C:3B:6B' => ['vendor' => 'MikroTik', 'device' => 'MikroTik RouterBOARD'],
        '74:4D:28' => ['vendor' => 'Ubiquiti Networks', 'device' => 'UniFi Access Point / Switch'],
        'F0:9F:C2' => ['vendor' => 'Ubiquiti Networks', 'device' => 'UniFi Controller / Switch'],
        'B4:FB:E4' => ['vendor' => 'Ubiquiti Networks', 'device' => 'UniFi Dream Machine / AP'],
        '14:14:4B' => ['vendor' => 'Ruijie Networks', 'device' => 'Ruijie / Reyee Switch / AP'],
        '70:A8:E3' => ['vendor' => 'Ruijie Networks', 'device' => 'Reyee Cloud Router / AP'],

        // TP-Link / D-Link / Tenda / Huawei / ZTE
        '94:18:65' => ['vendor' => 'TP-Link Corporation', 'device' => 'Router / Access Point TP-Link'],
        '50:D4:F7' => ['vendor' => 'TP-Link Corporation', 'device' => 'TP-Link Omada / Switch / AP'],
        'AC:84:C6' => ['vendor' => 'TP-Link Corporation', 'device' => 'TP-Link Wi-Fi Router'],
        'C0:C9:E3' => ['vendor' => 'TP-Link Corporation', 'device' => 'TP-Link Wireless Adapter'],
        '14:D6:4D' => ['vendor' => 'D-Link International', 'device' => 'D-Link Switch / Router'],
        '00:18:0A' => ['vendor' => 'Cisco / Meraki', 'device' => 'Meraki Cloud Managed AP / Switch'],
        'E8:65:D4' => ['vendor' => 'Tenda Technology', 'device' => 'Tenda Router / AP'],
        '48:57:02' => ['vendor' => 'Huawei Technologies', 'device' => 'Huawei Router / Switch / ONT'],
        '00:1E:10' => ['vendor' => 'Huawei Technologies', 'device' => 'Huawei GPON / ONT Modem'],
        '84:D8:1B' => ['vendor' => 'ZTE Corporation', 'device' => 'ZTE Router / ONT Modem'],

        // Virtualization (VMware, Hyper-V, Proxmox/QEMU, VirtualBox)
        '00:15:5D' => ['vendor' => 'Microsoft Corporation', 'device' => 'Hyper-V Virtual Machine Server'],
        '00:0C:29' => ['vendor' => 'VMware, Inc.', 'device' => 'VMware ESXi Virtual Machine'],
        '00:50:56' => ['vendor' => 'VMware, Inc.', 'device' => 'VMware Virtual Machine Server'],
        '08:00:27' => ['vendor' => 'Oracle VirtualBox', 'device' => 'VirtualBox Virtual Machine'],
        '52:54:00' => ['vendor' => 'QEMU / KVM / Proxmox', 'device' => 'Proxmox / KVM Virtual Machine'],

        // Storage / NAS / CCTV / NVR
        '00:11:32' => ['vendor' => 'Synology Incorporated', 'device' => 'NAS Storage Server Synology'],
        '00:11:34' => ['vendor' => 'Synology Incorporated', 'device' => 'NAS Storage Server Synology'],
        '00:08:9B' => ['vendor' => 'QNAP Systems, Inc.', 'device' => 'QNAP NAS Storage Server'],
        'BC:5E:2B' => ['vendor' => 'Hikvision Digital Technology', 'device' => 'Hikvision IP Camera / NVR'],
        'A4:14:37' => ['vendor' => 'Hikvision Digital Technology', 'device' => 'Hikvision CCTV / DVR'],
        'E0:50:8B' => ['vendor' => 'Dahua Technology', 'device' => 'Dahua IP Camera / NVR CCTV'],
        '90:02:A9' => ['vendor' => 'Dahua Technology', 'device' => 'Dahua Security Device'],

        // Printers & Copiers (Canon, Epson, Brother, Ricoh, Kyocera, Konica, Xerox)
        '7C:10:C9' => ['vendor' => 'Canon Inc.', 'device' => 'Network Printer Canon ImageRUNNER'],
        '00:00:85' => ['vendor' => 'Canon Inc.', 'device' => 'Network Printer Canon'],
        '38:22:D6' => ['vendor' => 'Canon Inc.', 'device' => 'Canon Network Multifunction Printer'],
        'EC:F4:BB' => ['vendor' => 'Epson Corporation', 'device' => 'Network Printer Epson L-Series/WorkForce'],
        '00:26:AB' => ['vendor' => 'Epson Corporation', 'device' => 'Epson Network Printer'],
        '00:80:77' => ['vendor' => 'Brother Industries', 'device' => 'Network Printer Brother MFC'],
        '30:05:5C' => ['vendor' => 'Brother Industries', 'device' => 'Brother Laser/Inkjet Printer'],
        '00:26:73' => ['vendor' => 'Ricoh Company Ltd.', 'device' => 'Ricoh Multifunction Copier/Printer'],
        '00:C0:EE' => ['vendor' => 'Kyocera Corporation', 'device' => 'Kyocera TASKalfa Copier/Printer'],
        '00:20:6B' => ['vendor' => 'Konica Minolta', 'device' => 'Konica Minolta bizhub Copier'],
        '00:00:AA' => ['vendor' => 'Xerox Corporation', 'device' => 'Xerox WorkCentre Printer'],

        // Mobile / Apple / Samsung / Xiaomi
        '30:CD:A7' => ['vendor' => 'Samsung Electronics', 'device' => 'Samsung Galaxy / Smart TV'],
        'F4:7B:5E' => ['vendor' => 'Apple, Inc.', 'device' => 'Apple Mac / iPhone / iPad'],
        'BC:D0:74' => ['vendor' => 'Apple, Inc.', 'device' => 'MacBook / iMac (Wi-Fi)'],
        'A4:C3:F0' => ['vendor' => 'Apple, Inc.', 'device' => 'Apple Device'],
        '64:CC:2E' => ['vendor' => 'Xiaomi Communications', 'device' => 'Xiaomi / Redmi Device'],
        '50:8A:06' => ['vendor' => 'Xiaomi Communications', 'device' => 'Xiaomi Smart Device / Phone'],
    ];

    /**
     * Resolve Details (Vendor & Probable Device Type) - 100% Instant Static Lookup
     */
    public static function resolveDetails(?string $mac): array
    {
        if (empty($mac)) {
            return ['vendor' => null, 'probable_device' => null];
        }

        $cleanMac = strtoupper(str_replace(['-', '.', ' '], ':', trim($mac)));
        $prefix = implode(':', array_slice(explode(':', $cleanMac), 0, 3));

        // Pure static local lookup - ZERO network latency guaranteed
        if (isset(self::$ouiDatabase[$prefix])) {
            return [
                'vendor' => self::$ouiDatabase[$prefix]['vendor'],
                'probable_device' => self::$ouiDatabase[$prefix]['device'],
            ];
        }

        return ['vendor' => null, 'probable_device' => 'Perangkat Jaringan (Unmapped MAC)'];
    }
}
