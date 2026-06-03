<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('account.addresses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'label' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ]);

        $nameParts = explode(' ', $request->name, 2);

        $data = [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $request->phone,
            'address_line_1' => $request->address_line1,
            'address_line_2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'label' => $request->label,
            'is_default' => $request->boolean('is_default'),
        ];

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        // If this is the first address, make it default
        if ($request->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $request->user()->addresses()->create($data);

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address added successfully.');
    }

    public function edit(Request $request, UserAddress $address): View
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        return view('account.addresses.edit', compact('address'));
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'label' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ]);

        $nameParts = explode(' ', $request->name, 2);

        $data = [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $request->phone,
            'address_line_1' => $request->address_line1,
            'address_line_2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'label' => $request->label,
            'is_default' => $request->boolean('is_default'),
        ];

        // If setting as default, unset other defaults
        if ($data['is_default']) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $wasDefault = $address->is_default;

        $address->delete();

        // If deleted address was default, make another address default
        if ($wasDefault) {
            $newDefault = $request->user()->addresses()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address deleted successfully.');
    }
}
