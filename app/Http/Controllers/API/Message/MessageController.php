<?php

namespace App\Http\Controllers\API\Message;

use App\Enums\NotificationType;
use App\Events\MessageEvent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FirebaseToken;
use App\Models\Message;
use App\Models\RestrictedWord;
use App\Models\User;
use App\Notifications\InfoNotification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

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
            $isRestricted = $restrictedWord ? 'true' : 'false';


            $recever = User::find($request->receiver_id);
            if (!$recever) {
                return Helper::jsonErrorResponse('Receiver not found', 404);
            }
            if ($this->user->role === $recever->role) {
                return Helper::jsonErrorResponse('You can not send message to same role user', 403);
            }
            $conversion_id = $this->user->id < $request->receiver_id
                ? $this->user->id . '-' . $request->receiver_id . '-' . $request->booking_id
                : $request->receiver_id . '-' . $this->user->id . '-' . $request->booking_id;

            $message = Message::create([
                'sender_id' => $this->user->id,
                'receiver_id' => $request->input('receiver_id'),
                'booking_id' => $request->input('booking_id'),
                'conversion_id' => $conversion_id,
                'content' => $request->input('content'),
                'is_restricted' => $isRestricted,
            ]);
            // Broadcast the message
            broadcast(new MessageEvent($message))->toOthers();

            // Sent notification to receiver
            $firebaseTokens = FirebaseToken::where('user_id', $message->receiver_id)->whereNotNull('token')->get();
            if (!empty($firebaseTokens)) {

                $notifyData = [
                    'title' => 'New Message Received',
                    'body' => 'There is a new message for you. Check it out!'
                ];
                foreach ($firebaseTokens as $tokens) {
                    if (!empty($tokens->token)) {
                        $token = $tokens->token; // Pluck tokens into an array
                        // Send notifications using the token array
                        Helper::sendNotifyMobile($token, $notifyData);
                    } else {
                        Log::warning('No Firebase tokens found for job post creator.');
                    }
                }
            } else {
                Log::warning('No Firebase tokens found for this user.');
            }
            //send notification in app
            $this->sendNotificationAndMail(
                $message?->receiver,
                'You have a new message from ' . $message?->sender?->name,
                'new_message_received',
                'New Message Received',
            );


            return Helper::jsonResponse(true, 'Sending Message successfully', 201, $message);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('NOt Sending Message !', 403, [$e->getMessage()]);
        }
    }

    //get message
    public function getMessage(Request $request)
    {
        $validateData = $request->validate([
            'conversion_id' => 'required|exists:messages,conversion_id',
        ]);
        try {
            $receiver_id = $this->user->id;
            $firstMessage = Message::where('conversion_id', $validateData['conversion_id'])->first();
            if (!$firstMessage) {
                return Helper::jsonResponse(true, 'No messages found.', 200, []);
            }

            $messages = Message::with([
                'sender:id,name,avatar,email',
                'booking:id,event_id,venue_id,name,location,booking_date,booking_start_time,booking_end_time,platform_rate,created_at,status',
                'booking.event:id,name,location,image',
                'booking.venue:id,name,location,image',
                'rating:id,name,rating',
            ])
                ->where('conversion_id', $validateData['conversion_id'])
                ->where(function ($query) use ($receiver_id) {
                    $query->where('sender_id', $receiver_id) // sender all message show
                        ->orWhere(function ($q) use ($receiver_id) {
                            $q->where('receiver_id', $receiver_id)
                                ->where('is_restricted', 'false'); // receiver restricted message not show
                        });
                })
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isEmpty()) {
                return Helper::jsonResponse(true, 'No messages found.', 200, []);
            }

            if ($messages->isEmpty()) {
                return Helper::jsonResponse(true, 'No messages found.', 200, []);
            }
         
            Message::where('conversion_id', $validateData['conversion_id'])
                ->where('receiver_id', $receiver_id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $firstMessage = $messages->first();
            // $sender = $firstMessage->sender;
            $booking = $firstMessage->booking;
            $rating = $firstMessage->rating;
            $getRecever = Message::where('conversion_id', $validateData['conversion_id'])->first();
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
                'messages' => $messageList,
                'recever_id' => $getRecever->receiver_id == Auth::user()->id ? $getRecever->sender_id : $getRecever->receiver_id


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
            $searchByName = $request->input('search_by_name');
            $userId = $this->user->id;

            $messages = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
                ->with(['sender', 'receiver'])
                ->when($searchByName, function ($query) use ($searchByName, $userId) {
                    $query->where(function ($q) use ($searchByName, $userId) {
                        $q->whereHas('sender', function ($q1) use ($searchByName, $userId) {
                            $q1->where('id', '!=', $userId)
                                ->where('name', 'like', '%' . $searchByName . '%');
                        })->orWhereHas('receiver', function ($q2) use ($searchByName, $userId) {
                            $q2->where('id', '!=', $userId)
                                ->where('name', 'like', '%' . $searchByName . '%');
                        });
                    });
                })

                ->orderBy('created_at', 'desc')
                ->get();

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

    /**
     * Retrieve all restricted words.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function RestrictedWords(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $query = RestrictedWord::query();

            if (!empty($search)) {
                $query->where('word', 'like', '%' . $search . '%');
            }

            $restrictedWords = $query->get();
            return Helper::jsonResponse(true, 'Restricted words retrieved successfully', 200, $restrictedWords);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve restricted words', 500, [$e->getMessage()]);
        }
    }


    public function sendNotificationAndMail($user = null, $messages = null, $message_type = null, $subject = null)
    {
        Log::info('notification type: ' . ($message_type));
        if ($user && $message_type) {
            $notificationData = [
                'title' => $subject,
                'message' => $messages,
                'url' => '',
                'message_type' => $message_type,
                'thumbnail' => asset('backend/admin/assets/images/messages_user.png' ?? ''),
                'user' => $this->user,
                'subject' => $subject,
            ];
            $user->notify(new InfoNotification($notificationData));
            Log::info('Notification sent to user: ' . $user->name);
        }
    }
}
