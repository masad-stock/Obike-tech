<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Transaction;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-financial-reports')->only(['generateReport']);
        $this->middleware('permission:manage-budgets')->only(['createBudget', 'updateBudget']);
        $this->middleware('permission:manage-transactions')->only(['createTransaction', 'updateTransaction']);
    }
    
    public function budgets()
    {
        $budgets = Budget::with('department')->orderBy('created_at', 'desc')->paginate(15);
        return view('financial.budgets.index', compact('budgets'));
    }
    
    public function createBudget(Request $request)
    {
        $departments = Department::all();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'description' => 'nullable|string',
                'status' => 'required|in:draft,active,closed',
            ]);
            
            $budget = Budget::create([
                'department_id' => $validated['department_id'],
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'created_by' => Auth::id(),
            ]);
            
            return redirect()->route('financial.budgets.show', $budget)
                ->with('success', 'Budget created successfully');
        }
        
        return view('financial.budgets.create', compact('departments'));
    }
    
    public function updateBudget(Request $request, Budget $budget)
    {
        $departments = Department::all();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'description' => 'nullable|string',
                'status' => 'required|in:draft,active,closed',
            ]);
            
            $budget->update($validated);
            
            return redirect()->route('financial.budgets.show', $budget)
                ->with('success', 'Budget updated successfully');
        }
        
        return view('financial.budgets.edit', compact('budget', 'departments'));
    }
    
    public function showBudget(Budget $budget)
    {
        $budget->load('department', 'createdBy', 'transactions');
        
        // Calculate spent amount
        $spentAmount = $budget->transactions->sum('amount');
        $remainingAmount = $budget->amount - $spentAmount;
        $utilizationPercentage = $budget->amount > 0 ? ($spentAmount / $budget->amount) * 100 : 0;
        
        // Group transactions by category
        $transactionsByCategory = $budget->transactions->groupBy('category');
        
        return view('financial.budgets.show', compact(
            'budget',
            'spentAmount',
            'remainingAmount',
            'utilizationPercentage',
            'transactionsByCategory'
        ));
    }
    
    public function transactions()
    {
        $transactions = Transaction::with('budget', 'createdBy')
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);
            
        return view('financial.transactions.index', compact('transactions'));
    }
    
    public function createTransaction(Request $request)
    {
        $budgets = Budget::where('status', 'active')->get();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'budget_id' => 'required|exists:budgets,id',
                'amount' => 'required|numeric|not_in:0',
                'transaction_date' => 'required|date',
                'description' => 'required|string',
                'category' => 'required|string|max:100',
                'reference_number' => 'nullable|string|max:100',
                'payment_method' => 'required|in:cash,bank_transfer,credit_card,check',
                'notes' => 'nullable|string',
            ]);
            
            // Check if budget has enough funds (for expenses)
            if ($validated['amount'] < 0) {
                $budget = Budget::find($validated['budget_id']);
                $spentAmount = $budget->transactions->sum('amount');
                $remainingAmount = $budget->amount + $spentAmount; // Adding because spent is negative
                
                if (abs($validated['amount']) > $remainingAmount) {
                    return back()->withErrors(['amount' => 'Transaction amount exceeds the remaining budget.']);
                }
            }
            
            $transaction = Transaction::create([
                'budget_id' => $validated['budget_id'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'reference_number' => $validated['reference_number'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);
            
            return redirect()->route('financial.transactions.show', $transaction)
                ->with('success', 'Transaction recorded successfully');
        }
        
        return view('financial.transactions.create', compact('budgets'));
    }
    
    public function updateTransaction(Request $request, Transaction $transaction)
    {
        $budgets = Budget::where('status', 'active')->get();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'budget_id' => 'required|exists:budgets,id',
                'amount' => 'required|numeric|not_in:0',
                'transaction_date' => 'required|date',
                'description' => 'required|string',
                'category' => 'required|string|max:100',
                'reference_number' => 'nullable|string|max:100',
                'payment_method' => 'required|in:cash,bank_transfer,credit_card,check',
                'notes' => 'nullable|string',
            ]);
            
            // Check if budget has enough funds (for expenses)
            if ($validated['amount'] < 0 && $validated['budget_id'] == $transaction->budget_id) {
                $budget = Budget::find($validated['budget_id']);
                $spentAmount = $budget->transactions->where('id', '!=', $transaction->id)->sum('amount');
                $remainingAmount = $budget->amount + $spentAmount; // Adding because spent is negative
                
                if (abs($validated['amount']) > $remainingAmount) {
                    return back()->withErrors(['amount' => 'Transaction amount exceeds the remaining budget.']);
                }
            }
            
            $transaction->update($validated);
            
            return redirect()->route('financial.transactions.show', $transaction)
                ->with('success', 'Transaction updated successfully');
        }
        
        return view('financial.transactions.edit', compact('transaction', 'budgets'));
    }

    public function showTransaction(Transaction $transaction)
    {
        $transaction->load('budget', 'createdBy');
        
        return view('financial.transactions.show', compact('transaction'));
    }
    
    public function generateReport(Request $request)
    {
        $reportType = $request->input('report_type', 'budget_summary');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        
        $data = [];
        
        switch ($reportType) {
            case 'budget_summary':
                $query = Budget::with('department');
                
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
                
                $budgets = $query->get();
                
                foreach ($budgets as $budget) {
                    $spentAmount = $budget->transactions->sum('amount');
                    $remainingAmount = $budget->amount - $spentAmount;
                    
                    $data[] = [
                        'id' => $budget->id,
                        'title' => $budget->title,
                        'department' => $budget->department->name,
                        'amount' => $budget->amount,
                        'spent' => $spentAmount,
                        'remaining' => $remainingAmount,
                        'utilization' => $budget->amount > 0 ? ($spentAmount / $budget->amount) * 100 : 0,
                        'status' => $budget->status,
                    ];
                }
                break;
                
            case 'transaction_history':
                $query = Transaction::with('budget.department')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
                    
                if ($departmentId) {
                    $query->whereHas('budget', function($q) use ($departmentId) {
                        $q->where('department_id', $departmentId);
                    });
                }
                
                $transactions = $query->orderBy('transaction_date', 'desc')->get();
                
                foreach ($transactions as $transaction) {
                    $data[] = [
                        'id' => $transaction->id,
                        'date' => $transaction->transaction_date,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'category' => $transaction->category,
                        'budget' => $transaction->budget->title,
                        'department' => $transaction->budget->department->name,
                        'payment_method' => $transaction->payment_method,
                    ];
                }
                break;
                
            case 'expense_by_category':
                $query = Transaction::with('budget.department')
                    ->where('amount', '<', 0)
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
                    
                if ($departmentId) {
                    $query->whereHas('budget', function($q) use ($departmentId) {
                        $q->where('department_id', $departmentId);
                    });
                }
                
                $expenses = $query->get();
                
                $categorySums = $expenses->groupBy('category')
                    ->map(function ($items) {
                        return abs($items->sum('amount'));
                    });
                    
                foreach ($categorySums as $category => $sum) {
                    $data[] = [
                        'category' => $category,
                        'amount' => $sum,
                        'percentage' => $expenses->sum('amount') != 0 ? ($sum / abs($expenses->sum('amount'))) * 100 : 0,
                    ];
                }
                break;
        }
        
        $departments = Department::all();
        
        return view('financial.reports.index', compact(
            'reportType',
            'startDate',
            'endDate',
            'departmentId',
            'data',
            'departments'
        ));
    }

    public function dashboard()
    {
        // Get current month's data
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Total budgets
        $totalBudgets = Budget::where('status', 'active')->sum('amount');
        
        // Total expenses this month
        $totalExpenses = Transaction::where('amount', '<', 0)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->sum('amount');
        
        // Total income this month
        $totalIncome = Transaction::where('amount', '>', 0)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->sum('amount');
        
        // Recent transactions
        $recentTransactions = Transaction::with('budget')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();
        
        // Monthly expenses for the current year
        $monthlyExpenses = Transaction::where('amount', '<', 0)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Expenses by department
        $expensesByDepartment = Transaction::where('amount', '<', 0)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->with('budget.department')
            ->get()
            ->groupBy('budget.department.name')
            ->map(function ($items) {
                return abs($items->sum('amount'));
            });
        
        return view('financial.dashboard', compact(
            'totalBudgets',
            'totalExpenses',
            'totalIncome',
            'recentTransactions',
            'monthlyExpenses',
            'expensesByDepartment'
        ));
    }
}

