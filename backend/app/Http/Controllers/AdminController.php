<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Доступ запрещен.'], 403);
            }
            return $next($request);
        });
    }

    // Dashboard statistics
    public function dashboard()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        return response()->json([
            'users_count' => User::count(),
            'drivers_count' => User::where('role', 'driver')->count(),
            'passengers_count' => User::where('role', 'passenger')->count(),
            'rides_count' => Ride::count(),
            'active_rides' => Ride::where('status', 'published')->count(),
            'bookings_count' => Booking::count(),
            'vehicles_count' => Vehicle::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'completed_rides' => Ride::where('status', 'completed')->count(),
            'total_revenue' => Booking::where('status', 'completed')->sum('price_total'),
            'rides_today' => Ride::whereDate('created_at', $today)->count(),
            'bookings_today' => Booking::whereDate('created_at', $today)->count(),
            'users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'role', 'created_at']),
            'recent_rides' => Ride::with(['driver', 'vehicle'])->latest()->take(5)->get(),
        ]);
    }

    // Users management
    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->withCount(['ridesAsDriver', 'bookings'])->paginate(20);

        return response()->json($users);
    }

    public function getUser(User $user)
    {
        $user->load(['vehicles', 'ridesAsDriver', 'bookings']);
        return response()->json($user);
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:passenger,driver,admin,manager',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|required|string|in:passenger,driver,admin,manager',
        ]);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function deleteUser(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Вы не можете удалить свой собственный аккаунт.'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    // Rides management
    public function getRides(Request $request)
    {
        $query = Ride::with(['driver', 'vehicle']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }

        $rides = $query->latest()->paginate(20);

        return response()->json($rides);
    }

    public function getRide(Ride $ride)
    {
        $ride->load(['driver', 'vehicle', 'bookings.passenger']);
        return response()->json($ride);
    }

    public function updateRide(Request $request, Ride $ride)
    {
        $request->validate([
            'status' => 'sometimes|required|string|in:pending,published,completed,cancelled',
        ]);

        $ride->update($request->only(['status']));

        return response()->json($ride->load(['driver', 'vehicle']));
    }

    public function deleteRide(Ride $ride)
    {
        $ride->delete();
        return response()->json(null, 204);
    }

    // Bookings management
    public function getBookings(Request $request)
    {
        $query = Booking::with(['ride.driver', 'ride.vehicle', 'passenger']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json($bookings);
    }

    public function getBooking(Booking $booking)
    {
        $booking->load(['ride.driver', 'ride.vehicle', 'passenger']);
        return response()->json($booking);
    }

    public function updateBooking(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'sometimes|required|string|in:pending,accepted,rejected,cancelled,completed',
        ]);

        $booking->update($request->only(['status']));

        return response()->json($booking->load(['ride.driver', 'passenger']));
    }

    public function deleteBooking(Booking $booking)
    {
        $booking->delete();
        return response()->json(null, 204);
    }

    // Vehicles management
    public function getVehicles(Request $request)
    {
        $query = Vehicle::with('owner');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $vehicles = $query->latest()->paginate(20);

        return response()->json($vehicles);
    }

    public function getVehicle(Vehicle $vehicle)
    {
        $vehicle->load(['owner', 'rides']);
        return response()->json($vehicle);
    }

    public function deleteVehicle(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->json(null, 204);
    }

    // Statistics
    public function stats()
    {
        return response()->json([
            'users_count' => User::count(),
            'drivers_count' => User::where('role', 'driver')->count(),
            'passengers_count' => User::where('role', 'passenger')->count(),
            'admins_count' => User::where('role', 'admin')->count(),
            'rides_count' => Ride::count(),
            'active_rides' => Ride::where('status', 'published')->count(),
            'completed_rides' => Ride::where('status', 'completed')->count(),
            'cancelled_rides' => Ride::where('status', 'cancelled')->count(),
            'bookings_count' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'accepted_bookings' => Booking::where('status', 'accepted')->count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'total_revenue' => Booking::where('status', 'completed')->sum('price_total'),
            'avg_booking_price' => Booking::where('status', 'completed')->avg('price_total'),
            'avg_ride_price' => Ride::where('status', 'published')->avg('price'),
            'vehicles_count' => Vehicle::count(),
        ]);
    }
}

