<?php

namespace App\Http\Controllers\API\Message;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use App\Models\RestrictedWord;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{

    //restiction  word  check
    protected function checkRestrictedWords($content)
    {
        $restrictedWords = RestrictedWord::pluck('word')->toArray();

        foreach ($restrictedWords as $word) {
            if (stripos($content, $word) !== false) {
                return $word;
            }
        }
        return false;
    }

    //send message 
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'booking_id' => 'required|exists:bookings,id',
                'content' => 'required',
            ]);

            $restrictedWord = $this->checkRestrictedWords($request->content);
            if ($restrictedWord) {
                return Helper::jsonErrorResponse('Restricted Word "' . $restrictedWord . '"not use', 422);
            }

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

    //get message
    public function getMessage(Request $request)
    {
        try {
            $receiver_id = Auth::user()->id;
            $messages = Message::with([
                'sender:id,name,avatar,email',
                'booking:id,event_id,name,location,booking_date,booking_start_time,booking_end_time,platform_rate,created_at',
                'booking.event:id,name,location,image',
                'rating:id,name,rating',
            ])
                ->where('receiver_id', $receiver_id)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                return Helper::jsonResponse(true, 'No messages found.', 200, []);
            }
            Message::where('receiver_id', $receiver_id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $firstMessage = $messages->first();
            $sender = $firstMessage->sender;
            $booking = $firstMessage->booking;
            $rating = $firstMessage->rating;

            $messageList = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at,
                ];
            });

            return Helper::jsonResponse(true, 'Messages fetched successfully.', 200, [
                'sender' => $sender,
                'booking' => $booking,
                'rating' => $rating,
                'messages' => $messageList
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
        }
    }


    //group message 
    public function GroupMessage(Request $request)
    {
        $userId = Auth::id();

        $conversionIds = $request->input('conversion_id');

        $messages = Message::where('conversion_id', $conversionIds)
            ->orderBy('created_at', 'asc')
            ->get();

        Message::where('conversion_id', $conversionIds)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }


    //get all list show
    public function listMessage(Request $request)
    {
        try {
            $conversations = Message::where('sender_id', Auth::user()->id)
                ->orWhere('receiver_id', Auth::user()->id)
                ->with(['sender', 'receiver'])
                ->latest()
                ->get()
                ->map(function ($message) {
                    return Auth::user()->id === $message->sender_id ? $message->receiver : $message->sender;
                })
                ->unique('id')
                ->values();

            return Helper::jsonResponse(true, 'all Message list show successfully', 200, $conversations);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed list show', 403, [$e->getMessage()]);
        }
    }
}
