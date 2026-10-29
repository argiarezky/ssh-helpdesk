<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">{{ isset($ticket) ? 'Ubah Tiket' : 'Buat Tiket' }}</h1></x-slot>
    <div class="ticket-create-form -mx-4 max-w-5xl overflow-hidden border border-[#cbd5df] bg-white shadow-[0_1px_3px_rgba(24,39,56,.06)] sm:mx-auto">
        <header class="flex items-center justify-between border-b border-[#bac6d3] px-3.5 py-3">
            <div>
                <h2 class="text-[12px] font-bold text-[#263442]">{{ isset($ticket) ? 'Edit Ticket' : 'Add New Ticket' }}</h2>
                <p class="mt-0.5 text-[9px] text-[#718091]">{{ isset($ticket) ? 'Update ticket information for '.$ticket->ticket_number : 'Enter ticket information to add it to the Kanban board' }}</p>
            </div>
            <a href="{{ isset($ticket) ? route('tickets.show', $ticket) : route('tickets.index') }}" aria-label="Close" title="Close" class="ml-3 rounded p-1 text-[#84909c] hover:bg-[#f1f4f7] hover:text-[#354555]"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg></a>
        </header>
        @php
            $deviceOptions = ['Router', 'Modem', 'Access Point', 'Switch', 'Firewall', 'CCTV', 'UPS', 'Rack', 'Perkabelan', 'SFP', 'Server', 'Lain-lain'];
            $coordinatorOptions = ['Ragil', 'Ihsan', 'Kholil', 'Nino', 'Reggy'];
            $engineerOptions = \App\Models\Ticket::RESPONSIBLE_ENGINEERS;
            $selectedResponsibleEngineer = old('assigned_engineer_name', $ticket->assigned_engineer_name ?? '');
            $selectedDevices = array_values(array_filter(array_map('trim', explode(',', (string) old('devices', $ticket->devices ?? '')))));
            $selectedCoordinators = array_values(array_filter(array_map('trim', explode(',', (string) old('coordinator_name', $ticket->coordinator_name ?? '')))));
            $selectedTechnicians = array_values(array_unique(array_filter(array_map('trim', array_merge(
                isset($ticket) ? [] : explode(',', (string) $selectedResponsibleEngineer),
                explode(',', (string) old('technician_name', $ticket->technician_name ?? '')),
            )))));
            $hasLegacyEngineer = isset($ticket) && $ticket->engineer_id && ! $ticket->assigned_engineer_name;
        @endphp
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ isset($ticket) ? route('tickets.update', $ticket) : route('tickets.store') }}"
            x-data="{
                deviceOptions: @js($deviceOptions),
                coordinatorOptions: @js($coordinatorOptions),
                engineerOptions: @js($engineerOptions),
                devices: @js($selectedDevices),
                coordinators: @js($selectedCoordinators),
                technicians: @js($selectedTechnicians),
                isEditing: @js(isset($ticket)),
                hasLegacyEngineer: @js($hasLegacyEngineer),
                projectName: @js(old('project_name', $ticket->project_name ?? '')),
                isAddingProject: false,
                newProjectName: '',
                deviceOtherOpen: false,
                coordinatorOtherOpen: false,
                technicianOtherOpen: false,
                otherDevice: '',
                otherCoordinator: '',
                otherTechnician: '',
                submitting: false,
                attemptedSubmit: false,
                addProject() {
                    const value = this.newProjectName.trim();
                    if (! value) return;
                    let option = Array.from(this.$refs.projectSelect.options).find(item => item.value === value);
                    if (! option) {
                        option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        this.$refs.projectSelect.add(option, this.$refs.projectSelect.options.length - 1);
                    }
                    this.projectName = value;
                    this.newProjectName = '';
                    this.isAddingProject = false;
                },
                toggleDevice(value) { this.devices = this.devices.includes(value) ? this.devices.filter(item => item !== value) : [...this.devices, value]; },
                toggleCoordinator(value) { this.coordinators = this.coordinators.includes(value) ? this.coordinators.filter(item => item !== value) : [...this.coordinators, value]; },
                toggleTechnician(value) { this.technicians = this.technicians.includes(value) ? this.technicians.filter(item => item !== value) : [...this.technicians, value]; },
                addDevice() { const value = this.otherDevice.trim(); if (value && !this.devices.includes(value)) this.devices.push(value); this.otherDevice = ''; this.deviceOtherOpen = false; },
                addCoordinator() { const value = this.otherCoordinator.trim(); if (value && !this.coordinators.includes(value)) this.coordinators.push(value); this.otherCoordinator = ''; this.coordinatorOtherOpen = false; },
                addTechnician() { const value = this.otherTechnician.trim(); if (value && !this.technicians.includes(value)) this.technicians.push(value); this.otherTechnician = ''; this.technicianOtherOpen = false; },
                setNow(id) { const current = new Date(); const localTime = new Date(current.getTime() - current.getTimezoneOffset() * 60000).toISOString().slice(0, 16); const field = document.getElementById(id); field.value = localTime; field.dispatchEvent(new Event('input', { bubbles: true })); field.dispatchEvent(new Event('change', { bubbles: true })); },
                validateSelections(event) { this.attemptedSubmit = true; if (this.coordinators.length === 0 || (!this.isEditing && !this.technicians.some(name => this.engineerOptions.includes(name)))) { event.preventDefault(); return; } this.submitting = true; }
            }"
            @submit="validateSelections($event)"
            class="p-3.5 sm:p-4"
        >
            @csrf
            @if (isset($ticket))
                @method('PUT')
            @endif
            @include('tickets._create-form')
        </form>
    </div>
</x-app-layout>
