<div class="space-y-3">
    @if ($errors->any())
        <div class="border border-[#c86d6d] bg-[#fff7f7] px-3 py-2 text-[10px] text-[#9b3f3f]" role="alert">
            <p class="font-semibold">Please check the following:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <input type="hidden" name="devices" :value="devices.join(', ')">
    <input type="hidden" name="coordinator_name" :value="coordinators.join(', ')">
    <input type="hidden" name="technician_name" :value="technicians.join(', ')">
    <input type="hidden" name="assigned_engineer_name" :value="technicians.find(name => engineerOptions.includes(name)) || ''">

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <p class="mb-1 text-[10px] font-semibold text-[#354555]">Ticket ID <span class="text-[#b94a48]">*</span></p>
            <div class="flex h-8 items-center rounded border border-[#bac6d3] bg-[#f7f9fc] px-2.5 font-mono text-[10px] text-[#778594]">Generated automatically</div>
        </div>
        <div>
            <label for="status" class="mb-1 block text-[10px] font-semibold text-[#354555]">Kanban Status <span class="text-[#b94a48]">*</span></label>
            <select id="status" name="status" required class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @foreach ($statuses as $option)
                    <option value="{{ $option }}" @selected(old('status', 'open') === $option)>{{ ['in_progress' => 'In Progress'][$option] ?? ucfirst($option) }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="priority" class="mb-1 block text-[10px] font-semibold text-[#354555]">Priority <span class="text-[#b94a48]">*</span></label>
            <select id="priority" name="priority" required class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @foreach ($priorities as $option)
                    <option value="{{ $option }}" @selected(old('priority', 'normal') === $option)>{{ ['normal' => 'Medium', 'high' => 'Major', 'urgent' => 'Critical'][$option] ?? ucfirst($option) }}</option>
                @endforeach
            </select>
            @error('priority')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
        <div>
            <label for="location" class="mb-1 block text-[10px] font-semibold text-[#354555]">Location / Site <span class="text-[#b94a48]">*</span></label>
            <input id="location" name="location" value="{{ old('location') }}" required maxlength="255" placeholder="e.g. BNI Cengkareng / SMAN 52" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('location')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="mb-1 flex items-center justify-between gap-2">
                <label for="project_name" class="text-[10px] font-semibold text-[#354555]">Project</label>
                <button type="button" @click="$refs.projectName.focus()" class="shrink-0 text-[9px] font-medium text-[#276749] hover:text-[#1f5139] focus:outline-none focus:underline">+ Add new project</button>
            </div>
            <input x-ref="projectName" id="project_name" name="project_name" list="ticket-projects" value="{{ old('project_name') }}" maxlength="255" placeholder="Select or enter a project" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
            <datalist id="ticket-projects">
                @foreach ($projects as $project)
                    <option value="{{ $project }}"></option>
                @endforeach
            </datalist>
            @error('project_name')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="description" class="mb-1 block text-[10px] font-semibold text-[#354555]">Issue Description <span class="text-[#b94a48]">*</span></label>
        <textarea id="description" name="description" rows="3" required maxlength="10000" placeholder="Technical issue summary or preventive maintenance details..." class="ticket-create-control w-full resize-y rounded border border-[#bac6d3] px-2.5 py-2 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">{{ old('description') }}</textarea>
        @error('description')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
    </div>

    <fieldset>
        <legend class="mb-1 text-[10px] font-semibold text-[#354555]">Device Issue <span class="font-normal text-[#84909c]">(select multiple)</span></legend>
        <div class="ticket-choice-box flex flex-wrap gap-1.5 rounded border border-[#bac6d3] bg-[#fbfcfe] p-2">
            @foreach ($deviceOptions as $deviceOption)
                <button type="button" @click="toggleDevice(@js($deviceOption))" :aria-pressed="devices.includes(@js($deviceOption)).toString()" :class="devices.includes(@js($deviceOption)) ? 'border-[#276749] bg-[#276749] text-white' : 'border-[#cbd5df] bg-white text-[#536271] hover:border-[#86b79b] hover:text-[#1f5139]'" class="rounded border px-2 py-1 text-[9px] font-medium transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">{{ $deviceOption }}</button>
            @endforeach
            <template x-for="customDevice in devices.filter(device => !deviceOptions.includes(device))" :key="customDevice">
                <button type="button" @click="toggleDevice(customDevice)" aria-pressed="true" class="rounded border border-[#276749] bg-[#276749] px-2 py-1 text-[9px] font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]"><span x-text="customDevice"></span><span aria-hidden="true"> ×</span></button>
            </template>
            <button type="button" @click="deviceOtherOpen = !deviceOtherOpen" class="rounded border border-dashed border-[#86b79b] bg-white px-2 py-1 text-[9px] font-medium text-[#276749] hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">+ Other...</button>
            <div x-cloak x-show="deviceOtherOpen" class="flex w-full gap-1.5 pt-1">
                <input x-model="otherDevice" @keydown.enter.prevent="addDevice()" type="text" maxlength="100" placeholder="Enter another device" class="ticket-create-control h-7 min-w-0 flex-1 rounded border border-[#bac6d3] px-2 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                <button type="button" @click="addDevice()" class="rounded bg-[#276749] px-2.5 text-[9px] font-semibold text-white hover:bg-[#1f5139]">Add</button>
            </div>
        </div>
        <p class="mt-1 text-[8px] italic text-[#84909c]">Click any option above to select. Multiple choices supported.</p>
    </fieldset>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
        <div>
            <label for="issue_category" class="mb-1 block text-[10px] font-semibold text-[#354555]">Issue Category</label>
            <input id="issue_category" name="issue_category" list="ticket-issue-categories" value="{{ old('issue_category') }}" maxlength="255" placeholder="Select or enter a category" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
            <datalist id="ticket-issue-categories">
                @foreach ($issueCategories as $issueCategory)
                    <option value="{{ $issueCategory }}"></option>
                @endforeach
            </datalist>
            @error('issue_category')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="action_type" class="mb-1 block text-[10px] font-semibold text-[#354555]">Handling Action</label>
            @php($actionTypes = ['Guide', 'Onsite', 'Remote'])
            <select id="action_type" name="action_type" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                <option value="">Select action</option>
                @foreach ($actionTypes as $actionType)
                    <option value="{{ $actionType }}" @selected(old('action_type') === $actionType)>{{ $actionType === 'Guide' ? 'Guide (Remote Guidance)' : $actionType }}</option>
                @endforeach
            </select>
            @error('action_type')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
    </div>

    <fieldset>
        <legend class="mb-1 flex w-full items-center justify-between gap-2 text-[10px] font-semibold text-[#354555]">
            <span>Coordinator <span class="font-normal text-[#84909c]">(select multiple)</span></span>
            <span class="rounded-full bg-green-50 px-2 py-0.5 text-[8px] font-semibold text-[#276749]" x-text="`${coordinators.length} selected`"></span>
        </legend>
        <div class="ticket-choice-box flex flex-wrap gap-1.5 rounded border border-[#bac6d3] bg-[#fbfcfe] p-2">
            @foreach ($coordinatorOptions as $coordinator)
                <button type="button" @click="toggleCoordinator(@js($coordinator))" :aria-pressed="coordinators.includes(@js($coordinator)).toString()" :class="coordinators.includes(@js($coordinator)) ? 'border-[#276749] bg-[#276749] text-white' : 'border-[#cbd5df] bg-white text-[#536271] hover:border-[#86b79b] hover:text-[#1f5139]'" class="rounded border px-2 py-1 text-[9px] font-medium transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">{{ $coordinator }}</button>
            @endforeach
            <template x-for="customCoordinator in coordinators.filter(coordinator => !coordinatorOptions.includes(coordinator))" :key="customCoordinator">
                <button type="button" @click="toggleCoordinator(customCoordinator)" aria-pressed="true" class="rounded border border-[#276749] bg-[#276749] px-2 py-1 text-[9px] font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]"><span x-text="customCoordinator"></span><span aria-hidden="true"> ×</span></button>
            </template>
            <button type="button" @click="coordinatorOtherOpen = !coordinatorOtherOpen" class="rounded border border-dashed border-[#86b79b] bg-white px-2 py-1 text-[9px] font-medium text-[#276749] hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">+ Other...</button>
            <div x-cloak x-show="coordinatorOtherOpen" class="flex w-full gap-1.5 pt-1">
                <input x-model="otherCoordinator" @keydown.enter.prevent="addCoordinator()" type="text" maxlength="100" placeholder="Enter another coordinator" class="ticket-create-control h-7 min-w-0 flex-1 rounded border border-[#bac6d3] px-2 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                <button type="button" @click="addCoordinator()" class="rounded bg-[#276749] px-2.5 text-[9px] font-semibold text-white hover:bg-[#1f5139]">Add</button>
            </div>
        </div>
        @error('coordinator_name')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        <p x-cloak x-show="attemptedSubmit && coordinators.length === 0" class="mt-1 text-[9px] text-[#b42318]" role="alert">Select at least one coordinator.</p>
    </fieldset>

    <fieldset>
        <legend class="mb-1 flex w-full items-center justify-between gap-2 text-[10px] font-semibold text-[#354555]">
            <span>Assigned Technicians <span class="font-normal text-[#84909c]">(Onsite / Remote · select multiple)</span></span>
            <span class="rounded-full bg-green-50 px-2 py-0.5 text-[8px] font-semibold text-[#276749]" x-text="`${technicians.length} selected`"></span>
        </legend>
        <div class="ticket-choice-box flex flex-wrap gap-1.5 rounded border border-[#bac6d3] bg-[#fbfcfe] p-2">
            @foreach ($engineerOptions as $engineer)
                <button type="button" @click="toggleTechnician(@js($engineer))" :aria-pressed="technicians.includes(@js($engineer)).toString()" :class="technicians.includes(@js($engineer)) ? 'border-[#276749] bg-[#276749] text-white' : 'border-[#cbd5df] bg-white text-[#536271] hover:border-[#86b79b] hover:text-[#1f5139]'" class="rounded border px-2 py-1 text-[9px] font-medium transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">{{ $engineer }}</button>
            @endforeach
            <template x-for="customTechnician in technicians.filter(technician => !engineerOptions.includes(technician))" :key="customTechnician">
                <button type="button" @click="toggleTechnician(customTechnician)" aria-pressed="true" class="rounded border border-[#276749] bg-[#276749] px-2 py-1 text-[9px] font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]"><span x-text="customTechnician"></span><span aria-hidden="true"> ×</span></button>
            </template>
            <button type="button" @click="technicianOtherOpen = !technicianOtherOpen" class="rounded border border-dashed border-[#86b79b] bg-white px-2 py-1 text-[9px] font-medium text-[#276749] hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-[#d7e8dc]">+ Other...</button>
            <div x-cloak x-show="technicianOtherOpen" class="flex w-full gap-1.5 pt-1">
                <input x-model="otherTechnician" @keydown.enter.prevent="addTechnician()" type="text" maxlength="100" placeholder="Enter another technician" class="ticket-create-control h-7 min-w-0 flex-1 rounded border border-[#bac6d3] px-2 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                <button type="button" @click="addTechnician()" class="rounded bg-[#276749] px-2.5 text-[9px] font-semibold text-white hover:bg-[#1f5139]">Add</button>
            </div>
        </div>
        @error('assigned_engineer_name')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        <p x-cloak x-show="attemptedSubmit && !technicians.some(name => engineerOptions.includes(name))" class="mt-1 text-[9px] text-[#b42318]" role="alert">Select at least one listed engineer to own this ticket.</p>
    </fieldset>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
        <div>
            <label for="reporter_name" class="mb-1 block text-[10px] font-semibold text-[#354555]">Reporter / PIC Name <span class="text-[#b94a48]">*</span></label>
            <input id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}" required maxlength="255" placeholder="Contact name" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('reporter_name')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="pic_contact" class="mb-1 block text-[10px] font-semibold text-[#354555]">Reporter Contact / Phone</label>
            <input id="pic_contact" name="pic_contact" value="{{ old('pic_contact') }}" maxlength="255" placeholder="+62..." class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('pic_contact')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
        <div>
            <div class="mb-1 flex items-center justify-between gap-1">
                <label for="created_at" class="text-[10px] font-semibold text-[#354555]">Reported At</label>
                <button type="button" @click="setNow('created_at')" class="text-[8px] font-medium text-[#276749] hover:text-[#1f5139] focus:outline-none focus:underline">Lapor Now</button>
            </div>
            <input id="created_at" name="created_at" type="datetime-local" value="{{ old('created_at', now()->format('Y-m-d\TH:i')) }}" class="ticket-create-control h-8 w-full min-w-0 rounded border border-[#bac6d3] px-1.5 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('created_at')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="mb-1 flex items-center justify-between gap-1">
                <label for="due_at" class="text-[10px] font-semibold text-[#354555]">Deadline</label>
            </div>
            <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at') }}" class="ticket-create-control h-8 w-full min-w-0 rounded border border-[#bac6d3] px-1.5 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('due_at')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="mb-1 flex items-center justify-between gap-1">
                <label for="resolved_at" class="text-[10px] font-semibold text-[#354555]">End Activities</label>
                <button type="button" @click="setNow('resolved_at')" class="text-[8px] font-medium text-[#276749] hover:text-[#1f5139] focus:outline-none focus:underline">Now</button>
            </div>
            <input id="resolved_at" name="resolved_at" type="datetime-local" value="{{ old('resolved_at') }}" class="ticket-create-control h-8 w-full min-w-0 rounded border border-[#bac6d3] px-1.5 text-[9px] focus:border-[#276749] focus:ring-[#d7e8dc]">
            @error('resolved_at')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="handling_notes" class="mb-1 block text-[10px] font-semibold text-[#354555]">Action Notes 1</label>
        <input id="handling_notes" name="handling_notes" value="{{ old('handling_notes') }}" maxlength="10000" placeholder="Initial troubleshooting actions..." class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">
        @error('handling_notes')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="team_plan" class="mb-1 block text-[10px] font-semibold text-[#354555]">Activity Summary / Resume</label>
        <textarea id="team_plan" name="team_plan" rows="2" maxlength="5000" placeholder="Resolution summary..." class="ticket-create-control w-full resize-y rounded border border-[#bac6d3] px-2.5 py-2 text-[10px] placeholder:text-[#98a3ad] focus:border-[#276749] focus:ring-[#d7e8dc]">{{ old('team_plan') }}</textarea>
        @error('team_plan')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-start gap-2 rounded border border-[#cbd5df] bg-[#f8fafc] px-2.5 py-2 text-[9px] text-[#64748b]">
        <svg class="mt-0.5 h-3 w-3 shrink-0 text-[#718096]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg>
        <p>Duration is calculated automatically from the reported and resolved time.</p>
    </div>

    <details class="border-t border-[#cbd5df] pt-2.5">
        <summary class="cursor-pointer text-[9px] font-semibold text-[#536271]">More ticket details and attachments</summary>
        <div class="mt-2 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <div>
                <label for="reporter_unit" class="mb-1 block text-[10px] font-semibold text-[#354555]">Reporter Unit</label>
                <input id="reporter_unit" name="reporter_unit" value="{{ old('reporter_unit') }}" maxlength="255" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('reporter_unit')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pic_name" class="mb-1 block text-[10px] font-semibold text-[#354555]">PIC Name</label>
                <input id="pic_name" name="pic_name" value="{{ old('pic_name') }}" maxlength="255" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('pic_name')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="legacy_ticket_number" class="mb-1 block text-[10px] font-semibold text-[#354555]">Reference Number</label>
                <input id="legacy_ticket_number" name="legacy_ticket_number" value="{{ old('legacy_ticket_number') }}" maxlength="80" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('legacy_ticket_number')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="report_source" class="mb-1 block text-[10px] font-semibold text-[#354555]">Report Source</label>
                @php($reportSources = ['WhatsApp', 'Telepon', 'Email'])
                <select id="report_source" name="report_source" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                    <option value="">Select source</option>
                    @foreach ($reportSources as $reportSource)
                        <option value="{{ $reportSource }}" @selected(old('report_source') === $reportSource)>{{ $reportSource }}</option>
                    @endforeach
                </select>
                @error('report_source')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="outage_category" class="mb-1 block text-[10px] font-semibold text-[#354555]">Outage Category</label>
                <input id="outage_category" name="outage_category" value="{{ old('outage_category') }}" maxlength="255" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('outage_category')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="delivery_status" class="mb-1 block text-[10px] font-semibold text-[#354555]">Deliver to Engineer</label>
                <input id="delivery_status" name="delivery_status" value="{{ old('delivery_status') }}" maxlength="100" placeholder="Done / NY" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('delivery_status')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="sla_target" class="mb-1 block text-[10px] font-semibold text-[#354555]">Target SLA</label>
                <input id="sla_target" name="sla_target" value="{{ old('sla_target') }}" maxlength="100" placeholder="3 hours / 1 day" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                @error('sla_target')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="response_template" class="mb-1 block text-[10px] font-semibold text-[#354555]">Response Template</label>
                <select id="response_template" onchange="if (this.value) document.getElementById('handling_notes').value = this.value" class="ticket-create-control h-8 w-full rounded border border-[#bac6d3] px-2.5 text-[10px] focus:border-[#276749] focus:ring-[#d7e8dc]">
                    <option value="">Select template</option>
                    @foreach ($responseTemplates as $template)
                        <option value="{{ $template->content }}">{{ $template->title }}</option>
                    @endforeach
                </select>
                <a href="{{ route('response-templates.index') }}" class="mt-1 inline-block text-[9px] font-medium text-[#276749] hover:text-[#1f5139]">Manage templates</a>
            </div>
            <div class="sm:col-span-2">
                <label for="attachments" class="mb-1 block text-[10px] font-semibold text-[#354555]">Attachments (optional)</label>
                <input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="ticket-create-control block w-full rounded border border-[#bac6d3] px-2.5 py-1.5 text-[9px] file:mr-2 file:rounded file:border-0 file:bg-[#edf3f7] file:px-2 file:py-1 file:text-[9px] file:font-semibold">
                <p class="mt-1 text-[8px] text-[#84909c]">JPG, PNG, WEBP, or PDF · max 10 MB per file · up to 5 files.</p>
                @error('attachments')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-[9px] text-[#b42318]">{{ $message }}</p>@enderror
            </div>
        </div>
    </details>

    <div class="flex flex-col-reverse justify-end gap-2 border-t border-[#cbd5df] pt-3 sm:flex-row">
        <a href="{{ route('tickets.index') }}" class="inline-flex h-8 items-center justify-center rounded border border-[#bac6d3] bg-white px-4 text-[10px] font-semibold text-[#536271] hover:bg-[#f5f7f9]">Cancel</a>
        <button type="submit" :disabled="submitting" class="inline-flex h-8 items-center justify-center gap-1.5 rounded bg-[#276749] px-4 text-[10px] font-semibold text-white transition hover:bg-[#1f5139] focus:outline-none focus:ring-2 focus:ring-[#b8d2c1] focus:ring-offset-1 disabled:cursor-wait disabled:opacity-60">
            <svg x-cloak x-show="submitting" class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.3A7.96 7.96 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.64z"/></svg>
            <span x-text="submitting ? 'Adding Ticket...' : 'Add Ticket'"></span>
        </button>
    </div>
</div>
