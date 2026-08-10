<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'patient',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->mergeGuestCart($request, $user);
        $this->mergeGuestAssessments($request, $user);

        return response()->json(['user' => $user]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $request->session()->regenerate();
        $this->mergeGuestCart($request, Auth::user());
        $this->mergeGuestAssessments($request, Auth::user());

        return response()->json(['user' => Auth::user()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * A cart built while browsing as a guest is tied to a `guest_token`
     * cookie rather than a user. On login/register, fold any such items into
     * the now-authenticated account so the cart the patient was building
     * doesn't just disappear the moment they sign in.
     */
    private function mergeGuestCart(Request $request, User $user): void
    {
        $token = $request->cookie('guest_cart_token');

        if (! $token) {
            return;
        }

        $existingProductIds = $user->cartItems()->pluck('product_id');

        CartItem::where('guest_token', $token)
            ->whereNotIn('product_id', $existingProductIds)
            ->update(['user_id' => $user->id, 'guest_token' => null]);

        CartItem::where('guest_token', $token)->delete();
    }

    /**
     * An assessment answered while browsing as a guest is tied to a
     * `guest_assessment_token` cookie rather than a user. On login/register,
     * claim it for the now-authenticated account — unlike the cart there's no
     * uniqueness constraint to dedupe against, so this is a plain reassign.
     */
    private function mergeGuestAssessments(Request $request, User $user): void
    {
        $token = $request->cookie('guest_assessment_token');

        if (! $token) {
            return;
        }

        Assessment::where('guest_token', $token)->update([
            'user_id' => $user->id,
            'guest_token' => null,
        ]);
    }
}
