<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Ride $ride)
    {
        return $ride->reviews()->with(['author', 'target'])->latest()->get();
    }

    public function store(StoreReviewRequest $request, Ride $ride)
    {
        $data = $request->validated();

        $authorId = $request->user()->id;
        $targetId = (int) $data['target_id'];

        abort_if($authorId === $targetId, 400, 'Нельзя оставлять отзыв самому себе.');

        $this->ensureReviewIsAllowed($ride, $authorId, $targetId);

        $review = Review::create([
            'ride_id' => $ride->id,
            'author_id' => $authorId,
            'target_id' => $targetId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $this->recalculateUserRating($targetId);

        return response()->json($review->load(['author', 'target']), 201);
    }

    protected function ensureReviewIsAllowed(Ride $ride, int $authorId, int $targetId): void
    {
        $isAuthorDriver = $ride->driver_id === $authorId;
        $isTargetDriver = $ride->driver_id === $targetId;
        $authorBookingExists = $ride->bookings()->where('passenger_id', $authorId)->exists();
        $targetBookingExists = $ride->bookings()->where('passenger_id', $targetId)->exists();

        $participants = ($isAuthorDriver || $authorBookingExists) && ($isTargetDriver || $targetBookingExists);

        abort_unless($participants, 403, 'Оставлять отзывы могут только участники поездки.');

        abort_if(
            Review::where('ride_id', $ride->id)
                ->where('author_id', $authorId)
                ->where('target_id', $targetId)
                ->exists(),
            400,
            'Вы уже оставили отзыв этому пользователю.'
        );
    }

    protected function recalculateUserRating(int $userId): void
    {
        $average = Review::where('target_id', $userId)->avg('rating') ?? 0;

        User::where('id', $userId)->update([
            'rating_average' => round($average, 2),
        ]);
    }

    public function myReviews(Request $request)
    {
        $reviews = Review::where('target_id', $request->user()->id)
            ->with(['author', 'ride'])
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}

