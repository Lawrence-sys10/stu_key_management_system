<?php

namespace App\Services;

use App\Models\Key;
use App\Models\KeyTag;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRService
{
    public function generateQRCode(KeyTag $keyTag, $size = 200)
    {
        $url = route('kiosk.scan-result', ['uuid' => $keyTag->uuid]);
        $content = "stu-keys://k/{$keyTag->uuid}";

        return QrCode::size($size)
            ->format('png')
            ->generate($content);
    }

    public function generateQRCodeForPrint(KeyTag $keyTag)
    {
        return $this->generateQRCode($keyTag, 150);
    }

    public function generatePrintSheet($keyTags)
    {
        $html = view('keys.print-sheet', compact('keyTags'))->render();
        
        // This would typically use DomPDF or similar
        // For now, return HTML that can be printed
        return $html;
    }

    public function storeQRCodeImage(KeyTag $keyTag)
    {
        $qrCode = $this->generateQRCode($keyTag, 300);
        $path = "qr-codes/{$keyTag->uuid}.png";
        
        Storage::disk('public')->put($path, $qrCode);
        
        return $path;
    }

    public function getQRCodeUrl(KeyTag $keyTag)
    {
        $path = "qr-codes/{$keyTag->uuid}.png";
        
        if (!Storage::disk('public')->exists($path)) {
            $this->storeQRCodeImage($keyTag);
        }
        
        return Storage::disk('public')->url($path);
    }
}
