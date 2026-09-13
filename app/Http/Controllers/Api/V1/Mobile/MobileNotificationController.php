<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class MobileNotificationController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:10,50',
            ],

            'unread_only' => [
                'nullable',
                'boolean',
            ],
        ]);

        $query = $request->user()
            ->notifications()
            ->latest();

        if (
            filter_var(
                $validated['unread_only'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
        ) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(
            $validated['per_page'] ?? 15
        );

        return response()->json([
            'success' => true,

            'data' => $notifications
                ->getCollection()
                ->map(
                    fn (
                        DatabaseNotification $notification
                    ) => $this->payload(
                        $notification
                    )
                )
                ->values(),

            'meta' => [
                'current_page' =>
                    $notifications->currentPage(),

                'last_page' =>
                    $notifications->lastPage(),

                'per_page' =>
                    $notifications->perPage(),

                'total' =>
                    $notifications->total(),
            ],

            'unread_count' =>
                $request->user()
                    ->unreadNotifications()
                    ->count(),
        ]);
    }

    public function unreadCount(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,

            'unread_count' =>
                $request->user()
                    ->unreadNotifications()
                    ->count(),
        ]);
    }

    public function markAsRead(
        Request $request,
        string $notification
    ): JsonResponse {
        $item = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if (!$item->read_at) {
            $item->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' =>
                'تم تعليم الإشعار كمقروء.',

            'data' => $this->payload(
                $item->fresh()
            ),

            'unread_count' =>
                $request->user()
                    ->unreadNotifications()
                    ->count(),
        ]);
    }

    public function markAllAsRead(
        Request $request
    ): JsonResponse {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم تعليم جميع الإشعارات كمقروءة.',

            'unread_count' => 0,
        ]);
    }

    public function destroy(
        Request $request,
        string $notification
    ): JsonResponse {
        $item = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $item->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف الإشعار.',

            'unread_count' =>
                $request->user()
                    ->unreadNotifications()
                    ->count(),
        ]);
    }

    private function payload(
        DatabaseNotification $notification
    ): array {
        $data = is_array($notification->data)
            ? $notification->data
            : [];

        return [
            'id' =>
                $notification->id,

            'notification_type' =>
                $data['notification_type']
                ?? null,

            'event' =>
                $data['event']
                ?? null,

            'title_ar' =>
                $data['title_ar']
                ?? 'إشعار جديد',

            'title_en' =>
                $data['title_en']
                ?? 'New Notification',

            'message_ar' =>
                $data['message_ar']
                ?? '',

            'message_en' =>
                $data['message_en']
                ?? '',

            'entity_type' =>
                $data['entity_type']
                ?? null,

            'entity_uuid' =>
                $data['entity_uuid']
                ?? null,

            'request_number' =>
                $data['request_number']
                ?? null,

            'status' =>
                $data['status']
                ?? null,

            'is_read' =>
                $notification->read_at !== null,

            'read_at' =>
                $notification->read_at
                    ?->toIso8601String(),

            'created_at' =>
                $notification->created_at
                    ?->toIso8601String(),
        ];
    }
}