<?php

namespace App\Http\Controllers\API\Message;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{

    // //sending message 
    // public function sendMessage(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'receiver_id' => 'required|exists:users,id',
    //             'content' => 'required|string',
    //         ]);
    //         $conversion_id = Auth::id() < $request->receiver_id ? Auth::id() . '-' . $request->receiver_id : $request->receiver_id . '-' . Auth::id();

    //         $message = Message::create([
    //             'sender_id' => Auth::user()->id,
    //             'receiver_id' => $request->input('receiver_id'),
    //             'conversion_id' => $conversion_id,

    //             'content' => $request->input('content'),
    //         ]);
    //         return Helper::jsonResponse(true, 'Sending Message successfully', 201, $message);
    //     } catch (Exception $e) {
    //         return Helper::jsonErrorResponse('Message sending Failed', 403, [$e->getMessage()]);
    //     }
    // }

    // public function getMessage(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'sender_id' => 'required',
    //         ]);

    //         $sender_id = $request->input('sender_id');
    //         $auth_id = Auth::user()->id;

    //         $messages = Message::where('sender_id', $sender_id)
    //             ->where('receiver_id', $auth_id)
    //             ->orderBy('created_at', 'asc')
    //             ->get();

    //         return Helper::jsonResponse(true, 'Messages fetched successfully.', 200, $messages);
    //     } catch (\Exception $e) {
    //         return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
    //     }
    // }


    // //get sender and reciver 
    // public function GroupMessage(Request $request)
    // {
    //     try {
    //         $userId = Auth::id();
    //         $conversionId = $request->input('conversion_id');
    //         $messages = Message::where('conversion_id', $conversionId)
    //             ->orderBy('created_at', 'asc')
    //             ->get();

    //         Message::where('conversion_id', $conversionId)
    //             ->where('receiver_id', $userId)
    //             ->where('is_read', false)
    //             ->update(['is_read' => true]);
    //         return Helper::jsonResponse(true, 'Get data successfully', 200, $messages);
    //     } catch (Exception $e) {
    //         return Helper::jsonErrorResponse('get Message Failed', 403, [$e->getMessage()]);
    //     }
    // }

    // //get all list show
    // public function listMessage(Request $request)
    // {
    //     try {
    //         $conversations = Message::where('sender_id', Auth::user()->id)
    //             ->orWhere('receiver_id', Auth::user()->id)
    //             ->with(['sender', 'receiver'])
    //             ->latest()
    //             ->get()
    //             ->map(function ($message) {
    //                 return Auth::user()->id === $message->sender_id ? $message->receiver : $message->sender;
    //             })
    //             ->unique('id')
    //             ->values();

    //         return Helper::jsonResponse(true, 'all Message list show successfully', 200, $conversations);
    //     } catch (Exception $e) {
    //         return Helper::jsonErrorResponse('Failed list show', 403, [$e->getMessage()]);
    //     }
    // }

    //----------------------------------------------------------------------------
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'booking_id' => 'required|exists:bookings,id',
                'content' => 'required',
            ]);

            $conversion_id = Auth::id() < $request->receiver_id
                ? Auth::id() . '-' . $request->receiver_id . '-' . $request->booking_id
                : $request->receiver_id . '-' . Auth::id() . '-' . $request->booking_id;

            $message = Message::create([
                'sender_id' => Auth::user()->id,
                'receiver_id' => $request->input('receiver_id'),
                'booking_id' => $request->input('booking_id'),
                'conversion_id' => $conversion_id,
                'content' => $request->input('content'),
            ]);

            return Helper::jsonResponse(true, 'Sending Message successfully', 201, $message);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('NOt Sending Message !', 403, [$e->getMessage()]);
        }
    }








    //------
    public function getMessage(Request $request)
    {
        try {
            $request->validate([
                'sender_id' => 'required',
            ]);

            $sender_id = $request->input('sender_id');
            $auth_id = Auth::user()->id;

            $messages = Message::with(['booking.event:id,name'])
                ->where(function ($query) use ($sender_id, $auth_id) {
                    $query->where('sender_id', $sender_id)->where('receiver_id', $auth_id)
                        ->orWhere(function ($q) use ($sender_id, $auth_id) {
                            $q->where('sender_id', $auth_id)->where('receiver_id', $sender_id);
                        });
                })
                ->with('rating:id,name,rating')
                ->orderBy('created_at', 'asc')
                ->get();

            return Helper::jsonResponse(true, 'Messages fetched successfully.', 200, $messages);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
        }
    }
}
