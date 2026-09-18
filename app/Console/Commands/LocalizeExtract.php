<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LocalizeExtract extends Command
{
    protected $signature = 'localize:extract
        {--update : Merge new strings without overwriting existing translations}';

    protected $description = 'Extract all __() strings into lang/en.json and lang/bn.json';

    private array $bnDictionary = [
        // Navigation & Layout
        'Dashboard' => 'ড্যাশবোর্ড',
        'Main' => 'প্রধান',
        'POS Terminal' => 'পস টার্মিনাল',
        'POS Settlement' => 'পস সেটেলমেন্ট',
        'POS Settings' => 'পস সেটিংস',
        'Live' => 'লাইভ',

        // Inventory
        'Products' => 'পণ্যসমূহ',
        'Product' => 'পণ্য',
        'Categories' => 'ক্যাটাগরি',
        'Category' => 'ক্যাটাগরি',
        'Brands' => 'ব্র্যান্ড',
        'Brand' => 'ব্র্যান্ড',
        'Units' => 'একক',
        'Warehouses' => 'গুদাম',
        'Warehouse' => 'গুদাম',
        'Stock' => 'স্টক',
        'Inventory' => 'ইনভেন্টরি',
        'Stock Levels' => 'স্টক লেভেল',
        'Stock Transfers' => 'স্টক ট্রান্সফার',
        'Stock Adjustments' => 'স্টক সমন্বয়',
        'Stock Ledger' => 'স্টক লেজার',
        'Barcodes' => 'বারকোড',
        'Variants' => 'ভেরিয়েন্ট',

        // Transactions
        'Sales' => 'বিক্রয়',
        'Sale' => 'বিক্রয়',
        'Purchases' => 'ক্রয়',
        'Purchase' => 'ক্রয়',
        'Quotations' => 'কোটেশন',
        'Sale Returns' => 'বিক্রয় ফেরত',
        'Purchase Returns' => 'ক্রয় ফেরত',
        'Invoices' => 'চালান',
        'Invoice' => 'চালান',

        // People
        'Customers' => 'গ্রাহক',
        'Customer' => 'গ্রাহক',
        'Suppliers' => 'সরবরাহকারী',
        'Supplier' => 'সরবরাহকারী',
        'Employees' => 'কর্মচারী',
        'Employee' => 'কর্মচারী',

        // Finance
        'Payments' => 'পেমেন্ট',
        'Payment' => 'পেমেন্ট',
        'Payment Accounts' => 'পেমেন্ট অ্যাকাউন্ট',
        'Expenses' => 'খরচ',
        'Expense' => 'খরচ',
        'Assets' => 'সম্পদ',
        'Loans' => 'ঋণ',
        'Loan' => 'ঋণ',
        'Accounting' => 'হিসাব',

        // Common Actions
        'Save' => 'সংরক্ষণ',
        'Cancel' => 'বাতিল',
        'Delete' => 'মুছুন',
        'Edit' => 'সম্পাদনা',
        'View' => 'দেখুন',
        'Add' => 'যোগ করুন',
        'Create' => 'তৈরি করুন',
        'Update' => 'আপডেট',
        'Search' => 'অনুসন্ধান',
        'Filter' => 'ফিল্টার',
        'Export' => 'রপ্তানি',
        'Import' => 'আমদানি',
        'Print' => 'প্রিন্ট',
        'Back' => 'পিছনে',
        'Submit' => 'জমা দিন',
        'Confirm' => 'নিশ্চিত',
        'Approve' => 'অনুমোদন',
        'Reject' => 'প্রত্যাখ্যান',
        'Close' => 'বন্ধ',
        'Actions' => 'কার্যক্রম',
        'Reset' => 'রিসেট',
        'Download' => 'ডাউনলোড',
        'Upload' => 'আপলোড',

        // Status
        'Status' => 'অবস্থা',
        'Active' => 'সক্রিয়',
        'Inactive' => 'নিষ্ক্রিয়',
        'Paid' => 'পরিশোধিত',
        'Unpaid' => 'অপরিশোধিত',
        'Partial' => 'আংশিক',
        'Pending' => 'অপেক্ষমান',
        'Completed' => 'সম্পন্ন',
        'Cancelled' => 'বাতিলকৃত',
        'Confirmed' => 'নিশ্চিত',
        'Draft' => 'খসড়া',
        'Approved' => 'অনুমোদিত',
        'Overdue' => 'মেয়াদোত্তীর্ণ',
        'Delivered' => 'বিতরণ হয়েছে',

        // Fields & Labels
        'Name' => 'নাম',
        'Phone' => 'ফোন',
        'Email' => 'ইমেইল',
        'Address' => 'ঠিকানা',
        'Date' => 'তারিখ',
        'Amount' => 'পরিমাণ',
        'Quantity' => 'পরিমাণ',
        'Price' => 'মূল্য',
        'Total' => 'মোট',
        'Subtotal' => 'উপমোট',
        'Discount' => 'ছাড়',
        'Tax' => 'কর',
        'Shipping' => 'শিপিং',
        'Note' => 'নোট',
        'Notes' => 'নোট',
        'Description' => 'বিবরণ',
        'Reference' => 'রেফারেন্স',
        'SKU' => 'এসকেইউ',
        'Barcode' => 'বারকোড',
        'Type' => 'ধরন',
        'Source' => 'উৎস',
        'Branch' => 'শাখা',
        'Branches' => 'শাখাসমূহ',

        // Reports & Analytics
        'Reports' => 'রিপোর্ট',
        'Report' => 'রিপোর্ট',
        'Analytics' => 'বিশ্লেষণ',
        'Profit & Loss' => 'লাভ-ক্ষতি',
        'Balance Sheet' => 'ব্যালেন্স শিট',
        'Cash Flow' => 'ক্যাশ ফ্লো',
        'Sales Report' => 'বিক্রয় রিপোর্ট',
        'Purchase Report' => 'ক্রয় রিপোর্ট',
        'Expense Report' => 'খরচ রিপোর্ট',
        'Stock Report' => 'স্টক রিপোর্ট',

        // HR
        'Attendance' => 'উপস্থিতি',
        'Leave' => 'ছুটি',
        'Payroll' => 'বেতন',

        // Settings
        'Settings' => 'সেটিংস',
        'General' => 'সাধারণ',
        'Notifications' => 'বিজ্ঞপ্তি',
        'Security' => 'নিরাপত্তা',
        'System' => 'সিস্টেম',
        'Profile' => 'প্রোফাইল',
        'Logout' => 'লগআউট',
        'Login' => 'লগইন',
        'Password' => 'পাসওয়ার্ড',
        'Change Password' => 'পাসওয়ার্ড পরিবর্তন',

        // eCommerce
        'eCommerce' => 'ইকমার্স',
        'Orders' => 'অর্ডার',
        'Online Orders' => 'অনলাইন অর্ডার',
        'Coupons' => 'কুপন',
        'Banners' => 'ব্যানার',
        'Collections' => 'কালেকশন',
        'Flash Deals' => 'ফ্ল্যাশ ডিল',
        'Blog' => 'ব্লগ',
        'Landing Pages' => 'ল্যান্ডিং পেজ',

        // Marketing
        'Marketing' => 'মার্কেটিং',
        'Ad Spend' => 'বিজ্ঞাপন খরচ',
        'SMS Campaigns' => 'এসএমএস ক্যাম্পেইন',
        'Loyalty' => 'লয়ালটি',

        // Manufacturing
        'Manufacturing' => 'উৎপাদন',
        'Production Orders' => 'প্রডাকশন অর্ডার',
        'Raw Materials' => 'কাঁচামাল',

        // Common phrases
        'Are you sure?' => 'আপনি কি নিশ্চিত?',
        'No data found' => 'কোনো তথ্য পাওয়া যায়নি',
        'Loading...' => 'লোড হচ্ছে...',
        'Showing' => 'দেখাচ্ছে',
        'of' => 'এর',
        'Previous' => 'পূর্ববর্তী',
        'Next' => 'পরবর্তী',
        'All' => 'সব',
        'Select' => 'নির্বাচন করুন',
        'Yes' => 'হ্যাঁ',
        'No' => 'না',
        'or' => 'অথবা',
        'and' => 'এবং',
        'from' => 'থেকে',
        'to' => 'পর্যন্ত',
        'Today' => 'আজ',
        'This Month' => 'এই মাস',
        'This Year' => 'এই বছর',

        // Time
        'Created' => 'তৈরি হয়েছে',
        'Updated' => 'আপডেট হয়েছে',
        'Created By' => 'তৈরি করেছেন',
        'Created At' => 'তৈরির তারিখ',

        // Flash messages
        'created successfully.' => 'সফলভাবে তৈরি হয়েছে।',
        'updated successfully.' => 'সফলভাবে আপডেট হয়েছে।',
        'deleted successfully.' => 'সফলভাবে মুছে ফেলা হয়েছে।',
    ];

    public function handle(): int
    {
        $update = $this->option('update');

        $this->info('🔍 Extracting __() strings from all files...');

        $strings = $this->extractStrings();

        $this->info("Found " . count($strings) . " unique translatable strings.");

        // Create lang directory
        $langDir = base_path('lang');
        if (!File::isDirectory($langDir)) {
            File::makeDirectory($langDir, 0755, true);
        }

        // Generate en.json (identity map)
        $enPath = "{$langDir}/en.json";
        $existingEn = [];
        if ($update && File::exists($enPath)) {
            $existingEn = json_decode(File::get($enPath), true) ?? [];
        }

        $enJson = array_merge($existingEn, array_combine($strings, $strings));
        ksort($enJson);

        File::put($enPath, json_encode($enJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("  <fg=green>✓</> lang/en.json — " . count($enJson) . " strings");

        // Generate bn.json (with dictionary translations + English fallback)
        $bnPath = "{$langDir}/bn.json";
        $existingBn = [];
        if ($update && File::exists($bnPath)) {
            $existingBn = json_decode(File::get($bnPath), true) ?? [];
        }

        $bnJson = [];
        $translated = 0;
        foreach ($strings as $str) {
            if (isset($existingBn[$str]) && $existingBn[$str] !== $str) {
                // Keep existing translation
                $bnJson[$str] = $existingBn[$str];
                $translated++;
            } elseif (isset($this->bnDictionary[$str])) {
                // Use dictionary translation
                $bnJson[$str] = $this->bnDictionary[$str];
                $translated++;
            } else {
                // Fallback: use English (to be translated later)
                $bnJson[$str] = $str;
            }
        }
        ksort($bnJson);

        File::put($bnPath, json_encode($bnJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("  <fg=green>✓</> lang/bn.json — " . count($bnJson) . " strings ({$translated} translated, " . (count($bnJson) - $translated) . " need translation)");

        $this->newLine();
        $this->info("✅ Done! Translation files generated.");
        $this->line("  Strings needing Bengali translation: <fg=yellow>" . (count($bnJson) - $translated) . "</>");

        return 0;
    }

    private function extractStrings(): array
    {
        $strings = [];

        // Scan Blade files
        $bladeFiles = $this->getFiles(base_path('Modules'), '*.blade.php');
        foreach ($bladeFiles as $file) {
            $content = File::get($file);
            // Match __('...') and __("...")
            preg_match_all("/__\(\s*['\"](.+?)['\"]\s*\)/", $content, $matches);
            foreach ($matches[1] as $str) {
                $str = stripslashes($str);
                $strings[$str] = true;
            }
        }

        // Scan PHP files (controllers, services)
        $phpFiles = $this->getFiles(base_path('Modules'), '*.php', 'app');
        foreach ($phpFiles as $file) {
            $content = File::get($file);
            preg_match_all("/__\(\s*['\"](.+?)['\"]\s*\)/", $content, $matches);
            foreach ($matches[1] as $str) {
                $str = stripslashes($str);
                $strings[$str] = true;
            }
        }

        // Also scan app/ directory
        $appFiles = $this->getFiles(base_path('app'), '*.php');
        foreach ($appFiles as $file) {
            $content = File::get($file);
            preg_match_all("/__\(\s*['\"](.+?)['\"]\s*\)/", $content, $matches);
            foreach ($matches[1] as $str) {
                $str = stripslashes($str);
                $strings[$str] = true;
            }
        }

        $result = array_keys($strings);
        sort($result);

        return $result;
    }

    private function getFiles(string $base, string $pattern, ?string $subDir = null): array
    {
        $files = [];

        if ($subDir) {
            $dirs = glob("{$base}/*/{$subDir}", GLOB_ONLYDIR);
        } else {
            $dirs = [$base];
        }

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
