<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('user')->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', fn($q) => $q->where('email', 'like', '%' . $request->search . '%'));
        }

        $transactions = $query->paginate(50)->withQueryString();
        $totalRevenue = Transaction::where('type', 'purchase')->sum('price_paid');

        return view('admin.transactions.index', compact('transactions', 'totalRevenue'));
    }
}
