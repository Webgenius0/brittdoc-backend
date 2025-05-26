<?php

namespace App\Http\Controllers\API\Message;

use App\Events\MessageEvent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use App\Models\RestrictedWord;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    protected $user;
    public function __construct()
    {
        $this->user = Auth::user();
    }

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
            $recever = User::find($request->receiver_id);
            if (!$recever) {
                return Helper::jsonErrorResponse('Receiver not found', 404);
            }
            if ($this->user->role === $recever->role) {
                return Helper::jsonErrorResponse('You can not send message to same role user', 403);
            }
            $conversion_id = $this->user->id < $request->receiver_idL
                ? $this->user->id . '-' . $request->receiver_id . '-' . $request->booking_id
                : $request->receiver_id . '-' . $this->user->id . '-' . $request->booking_id;

            $message = Message::create([
                'sender_id' => $this->user->id,
                'receiver_id' => $request->input('receiver_id'),
                'booking_id' => $request->input('booking_id'),
                'conversion_id' => $conversion_id,
                'content' => $request->input('content'),
            ]);
            // Broadcast the message
            broadcast(new MessageEvent($message))->toOthers();
            return Helper::jsonResponse(true, 'Sending Message successfully', 201, $message);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('NOt Sending Message !', 403, [$e->getMessage()]);
        }
    }

    //get message
    public function getMessage(Request $request)
    {
        try {
            $receiver_id = $this->user->id;
            $messages = Message::with([
                'sender:id,name,avatar,email',
                'booking:id,event_id,name,location,booking_date,booking_start_time,booking_end_time,platform_rate,created_at',
                'booking.event:id,name,location,image',
                'rating:id,name,rating',
            ])
                ->where('receiver_id', $receiver_id)
                ->orWhere('sender_id', $receiver_id)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                return Helper::jsonResponse(true, 'No messages found.', 200, []);
            }
            Message::where('receiver_id', $receiver_id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $firstMessage = $messages->first();
            // $sender = $firstMessage->sender;
            $booking = $firstMessage->booking;
            $rating = $firstMessage->rating;

            $messageList = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'avatar' => $message->sender->avatar,
                        'email' => $message->sender->email,
                    ],
                ];
            });

            return Helper::jsonResponse(true, 'Messages fetched successfully.', 200, [
                // 'sender' => $sender,
                'booking' => $booking,
                'rating' => $rating ?? 0,
                'messages' => $messageList
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
        }
    }


    //group message 
    public function GroupMessage(Request $request)
    {
        $userId = $this->user->id;

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



    /**
     * Retrieve all message conversations for the current user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chatList(Request $request): JsonResponse
    {
        try {
            $userId = $this->user->id;

            // Get all messages involving the user
            $messages = Message::where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Group messages by conversation
            $grouped = $messages->groupBy('conversion_id');
            $conversations = $grouped->map(function ($group) use ($userId) {
                $lastMessage = $group->sortByDesc('created_at')->first();

                $opponent = $lastMessage->sender_id === $userId
                    ? $lastMessage->receiver
                    : $lastMessage->sender;

                $unreadCount = $group->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->count();

                return [
                    'conversion_id' => $lastMessage->conversion_id,
                    'user' => [
                        'id' => $opponent->id,
                        'name' => $opponent->name,
                        'avatar' => $opponent->avatar,
                    ],
                    'unread_count' => $unreadCount,
                    'is_read' => $lastMessage->is_read ?? false,
                    'last_message' => [
                        'content' => $lastMessage->content,
                        'created_at' => $lastMessage->created_at->format('Y-m-d g:i:s A') ?? '',
                    ],

                ];
            })->values();

            return Helper::jsonResponse(true, 'All message conversations retrieved successfully', 200, $conversations);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to list messages', 403, [$e->getMessage()]);
        }
    }

}
