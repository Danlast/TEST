<?php

namespace App\Http\Controllers;

use App\Models\BookExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookExchangeController extends Controller
{
    public function index(Request $request)
    {
        $exchanges = BookExchange::with(['user', 'bookedByUser'])->latest()->paginate(12);
        $exchanges->appends($request->query());
        $exchangeMarkers = BookExchange::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'title', 'latitude', 'longitude', 'place']);

        return view('pages.books.exchange_index', compact('exchanges', 'exchangeMarkers'));
    }

    public function create()
    {
        return view('pages.books.exchange_create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'place' => 'required|string|max:255',
            'date' => 'nullable|date',
            'contacts' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Проверьте форму и заполните обязательные поля.');
        }

        $validated = $validator->validated();
        $validated['description'] = $validated['description'] ?? '';

        if ($request->filled('date')) {
            $validated['date'] = $request->input('date');
        } else {
            $validated['date'] = null;
        }

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';

        BookExchange::create($validated);

        return redirect()->route('exchange.index')->with('success', 'Объявление создано');
    }

    public function show(BookExchange $exchange)
    {
        return view('pages.books.exchange_show', compact('exchange'));
    }

    public function edit(BookExchange $exchange)
    {
        abort_unless($exchange->canBeManagedBy(Auth::user()), 403);

        return view('pages.books.exchange_edit', compact('exchange'));
    }

    public function update(Request $request, BookExchange $exchange)
    {
        abort_unless($exchange->canBeManagedBy(Auth::user()), 403);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'place' => 'required|string|max:255',
            'date' => 'nullable|date',
            'contacts' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Проверьте форму и заполните обязательные поля.');
        }

        $validated = $validator->validated();
        $validated['description'] = $validated['description'] ?? '';

        if ($request->filled('date')) {
            $validated['date'] = $request->input('date');
        } else {
            $validated['date'] = null;
        }

        $exchange->update($validated);

        return redirect()->route('exchange.show', $exchange)->with('success', 'Объявление обновлено');
    }

    public function delete(BookExchange $exchange)
    {
        abort_unless($exchange->canBeManagedBy(Auth::user()), 403);

        return view('pages.books.exchange_delete', compact('exchange'));
    }

    public function destroy(BookExchange $exchange)
    {
        if (! Auth::user() || (! $exchange->canBeManagedBy(Auth::user()) && ! Auth::user()->role?->isStaff())) {
            abort(403);
        }

        $exchange->delete();

        return redirect()->route('exchange.index')->with('success', 'Объявление удалено');
    }

    public function book(BookExchange $exchange)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if ($exchange->status === 'booked') {
            return back()->with('error', 'Книга уже забронирована');
        }

        $exchange->update([
            'status' => 'booked',
            'booked_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Книга забронирована');
    }

    public function unbook(BookExchange $exchange)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if ($exchange->booked_by_user_id !== Auth::id()) {
            abort(403);
        }

        $exchange->update([
            'status' => 'active',
            'booked_by_user_id' => null,
        ]);

        return back()->with('success', 'Бронирование отменено');
    }
}
