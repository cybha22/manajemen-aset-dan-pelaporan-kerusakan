<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function generate($roomId)
    {
        $room = Room::with('building')->findOrFail($roomId);

        $url = config('app.url') . '/lapor?g=' . $room->building->code . '&r=' . $room->room_number;

        $qrCode = QrCode::format('png')
            ->size(400)
            ->margin(2)
            ->generate($url);

        return response($qrCode)
            ->header('Content-Type', 'image/png');
    }
}
