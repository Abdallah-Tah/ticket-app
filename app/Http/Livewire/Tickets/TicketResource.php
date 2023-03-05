<?php

namespace App\Http\Livewire\Tickets;

use App\Models\Ticket;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class TicketResource extends Component
{
    use WithPagination, WithFileUploads;
    public $ticketId;
    public $search;
    public $sortBy = 'id';
    public $title, $requestor_name, $department_id, $is_notified, $plan_id, $category_id, $claim_number, $problem_statement, $attachment, $target_date;

    public $sortDirection = 'asc';
    public $perPage = 10;
    public $page = 1;
    protected $tickets;

    public $showModal = false;
    public $showDeleteModal = false;

    protected $listeners = ['ticketCreated' => 'render', 'ticketUpdated' => 'render'];

    public function sortBy($field)
    {
        if ($this->sortDirection == 'asc') {
            $this->sortDirection = 'desc';
        } else {
            $this->sortDirection = 'asc';
        }

        return $this->sortBy = $field;
    }

    public function mount()
    {
        //$this->tickets = Ticket::with('plan', 'category')->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    public function showModal()
    {
        $this->reset();
        $this->showModal = true;
    }

    public function showDeleteModal($id)
    {
        $this->ticketId = $id;
        $this->showDeleteModal = true;
    }

    public function render()
    {
        $tickets = Ticket::with('plan', 'category')
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('requestor_name', 'like', '%' . $this->search . '%')
                    ->orWhere('claim_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })

            ->paginate($this->perPage, ['*'], 'page', $this->page);

        $departments = Auth::user()->departments()->get();
        $plans = Auth::user()->plans()->get();
        $categories = Auth::user()->categories()->get();

        return view('livewire.tickets.ticket-resource', compact('tickets', 'departments', 'plans', 'categories'));
    }

    public function storeTicket()
    {
        $this->validate([
            'title' => 'required',
            //'requestor_name' => 'required',
            'department_id' => 'required',
            'is_notified' => 'required',
            'plan_id' => 'required',
            'category_id' => 'required',
            'claim_number' => 'required',
            'problem_statement' => 'required',
            //'attachment' => 'required',
            'target_date' => 'required',
        ]);

        $tickets = Ticket::create([
            'title' => $this->title,
            'requestor_name' => Auth::user()->name,
            'department_id' => $this->department_id,
            'is_notified' => $this->is_notified,
            'plan_id' => $this->plan_id,
            'category_id' => $this->category_id,
            'claim_number' => $this->claim_number,
            'problem_statement' => $this->problem_statement,
            'attachment' => $this->attachment,
            'target_date' => $this->target_date,
            'user_id' => Auth::user()->id,
        ]);

        $this->reset();
        $this->showModal = false;
        $this->dispatchBrowserEvent('notify', 'Ticket Created Successfully!, Your ticket number is ' . $tickets->id);
    }

    public function editTicket($id)
    {
        $ticket = Ticket::find($id);
        $this->ticketId = $id;
        $this->title = $ticket->title;
        //$this->requestor_name = $ticket->requestor_name;
        $this->department_id = $ticket->department_id;
        $this->is_notified = $ticket->is_notified;
        $this->plan_id = $ticket->plan_id;
        $this->category_id = $ticket->category_id;
        $this->claim_number = $ticket->claim_number;
        $this->problem_statement = $ticket->problem_statement;
        $this->attachment = $ticket->attachment;
        $this->target_date = $ticket->target_date;

        $this->showModal = true;
    }

    public function updateTicket()
    {

        $this->validate([
            'title' => 'required',
            //'requestor_name' => 'required',
            'department_id' => 'required',
            'is_notified' => 'required',
            'plan_id' => 'required',
            'category_id' => 'required',
            'claim_number' => 'required',
            'problem_statement' => 'required',
            //'attachment' => 'required',
            'target_date' => 'required',
        ]);

        if ($this->ticketId) {
            $ticket = Ticket::find($this->ticketId);
            $this->authorize('update', $ticket);
            $ticket->update([
                'title' => $this->title,
                //'requestor_name' => $this->requestor_name,
                'department_id' => $this->department_id,
                'is_notified' => $this->is_notified,
                'plan_id' => $this->plan_id,
                'category_id' => $this->category_id,
                'claim_number' => $this->claim_number,
                'problem_statement' => $this->problem_statement,
                'attachment' => $this->attachment,
                'target_date' => $this->target_date,
            ]);
            $this->reset();
            $this->showModal = false;
            $this->dispatchBrowserEvent('notify', 'Ticket Updated Successfully!');
        }
    }


    public function delete()
    {
        $ticket = Ticket::find($this->ticketId);
        $this->authorize('delete', $ticket);
        $ticket->delete();
        $this->showDeleteModal = false;
        $this->dispatchBrowserEvent('notify', 'Ticket Deleted Successfully!');
    }


    public function resetInputFields()
    {
        $this->title = '';
        $this->requestor_name = '';
        $this->department_id = '';
        $this->is_notified = '';
        $this->plan_id = '';
        $this->category_id = '';
        $this->claim_number = '';
        $this->problem_statement = '';
        $this->attachment = '';
        $this->target_date = '';
    }

    public function download($id)
    {
        $ticket = Ticket::find($id);
        $file = $ticket->attachment;
        $path = public_path('storage/' . $file);
        return response()->download($path);
    }
}
