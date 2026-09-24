@push('styles')
<style>
    .assignment-form-card { width:100%; padding:0 !important; background:transparent !important; border:0 !important; border-radius:0; box-shadow:none !important; }
    #priorityAssignmentForm { width:100%; margin:0 auto; }
    .assignment-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px 15px; }
    .assignment-fields > .assignment-control-group { width:100%; }
    .assignment-priority-group { grid-column:1 / -1; }
    .assignment-fields label { margin-bottom:5px; }
    .assignment-fields .input { min-height:38px !important; height:38px; padding:7px 10px !important; font-size:11px !important; }
    .assignment-field-note { min-height:0; margin-top:4px; color:var(--muted); font-size:9px; line-height:1.4; }
    .priority-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; padding:8px; background:rgba(255,255,255,.38); border:1px solid rgba(69,6,147,.13); border-radius:10px; }
    .priority-row { display:grid; grid-template-columns:105px 1fr; align-items:center; gap:9px; padding:7px; background:rgba(255,255,255,.72); border:1px solid rgba(69,6,147,.1); border-radius:8px; }
    .priority-label { display:flex; align-items:center; gap:8px; color:var(--navy); font-size:11px; font-weight:800; }
    .priority-number { width:25px; height:25px; display:grid; place-items:center; color:white; background:var(--primary); border-radius:7px; }
    .priority-row:first-child { border-color:rgba(69,6,147,.34); box-shadow:0 7px 18px rgba(69,6,147,.07); }
    .priority-row:first-child .priority-number { background:linear-gradient(135deg,var(--primary),var(--primary-light)); }
    .priority-combobox { position:relative; min-width:0; }
    .priority-search { width:100%; min-width:0; }
    .priority-options { position:absolute; z-index:30; top:calc(100% + 4px); right:0; left:0; max-height:180px; overflow-y:auto; padding:4px; background:#fff; border:1px solid rgba(69,6,147,.28); border-radius:8px; box-shadow:0 12px 24px rgba(47,16,82,.16); }
    .priority-option { display:block; width:100%; padding:8px 9px; color:var(--navy); text-align:left; font:inherit; font-size:10px; font-weight:700; background:transparent; border:0; border-radius:5px; cursor:pointer; }
    .priority-option:hover,.priority-option:focus-visible { color:#fff; background:var(--primary); outline:0; }
    .priority-no-results { margin:0; padding:8px 9px; color:var(--muted); font-size:10px; }
    .priority-empty { display:none; margin:0; padding:14px; color:var(--muted); text-align:center; font-size:10px; background:rgba(255,255,255,.66); border-radius:8px; }
    .priority-explanation { margin-top:7px; padding:9px 11px; color:#584663; background:#f7f1fb; border-left:3px solid var(--primary); border-radius:7px; font-size:9px; line-height:1.5; }
    .assignment-form-card .admin-profile-actions { margin-top:18px; }
    @media(max-width:640px) {
        .assignment-fields,.priority-list { grid-template-columns:1fr; }
        .assignment-priority-group { grid-column:auto; }
        .priority-row { grid-template-columns:1fr; gap:7px; }
    }
</style>
@endpush

@php
    $selectedSubjectId=(string)old('subject_id',$selectedSubject?->id);
    $selectedInstructorIds=collect(old('instructor_ids',$selectedSubject?->instructors->pluck('id')->all()??[]))
        ->filter(fn($id)=>filled($id))->map(fn($id)=>(int)$id)->values()->all();
@endphp

@push('portal-profile-overlay')
<div id="assignmentFormModal" class="admin-profile-modal" hidden>
<section id="assignment-form" class="admin-profile-dialog" role="dialog" aria-modal="true" aria-labelledby="assignmentFormTitle">
    <header class="admin-profile-header">
        <div>
            <h2 id="assignmentFormTitle">Assign Instructor to a Subject</h2>
            <p>Select the semester, year level, subject, and instructor department. Selecting another department sends its Dean an instructor request.</p>
        </div>
        <button id="closeAssignmentForm" class="admin-profile-close" type="button" aria-label="Close assignment form">&times;</button>
    </header>

<div class="card assignment-form-card">
    <form id="priorityAssignmentForm" method="POST" action="{{ route('dean.subject-assignments.store') }}">
        @csrf
        <input type="hidden" name="assignment_modal" value="1">
        <input type="hidden" name="return_search" value="{{ request('search') }}">
        <input type="hidden" name="return_year_level" value="{{ request('year_level') }}">
        <input type="hidden" name="return_semester" value="{{ request('semester') }}">
        <input type="hidden" name="return_curriculum" value="{{ request('curriculum') }}">
        <input type="hidden" name="return_assignment_status" value="{{ request('assignment_status') }}">

        <div class="assignment-fields">
            <div class="assignment-control-group">
                <label for="semester">Semester</label>
                <select id="semester" class="input" name="semester" required>
                    <option value="">Select a semester</option>
                    @foreach($semesters as $semesterOption)
                        <option value="{{ $semesterOption }}" @selected($selectedSemester===$semesterOption)>
                            {{ $semesterOption==='1st'?'First Semester':($semesterOption==='2nd'?'Second Semester':'Summer') }}
                        </option>
                    @endforeach
                </select>
                @error('semester')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="assignment-control-group">
                <label for="year_level">Year Level</label>
                <select id="year_level" class="input" name="year_level" required>
                    <option value="">Select a year level</option>
                    @foreach([1=>'First Year',2=>'Second Year',3=>'Third Year',4=>'Fourth Year'] as $level=>$label)
                        <option value="{{ $level }}" @selected($selectedYearLevel===(string)$level)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('year_level')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="assignment-control-group">
                <label for="subject_id">Subject</label>
                <select id="subject_id" class="input" name="subject_id" required>
                    <option value="">Select a subject</option>
                    @foreach($subjectOptions->groupBy('year_level') as $yearLevel=>$yearSubjects)
                        <optgroup data-year-group="{{ $yearLevel }}" label="{{ match((int)$yearLevel){1=>'First Year',2=>'Second Year',3=>'Third Year',4=>'Fourth Year',default=>'Year '.$yearLevel} }}">
                            @foreach($yearSubjects as $subjectOption)
                                <option
                                    value="{{ $subjectOption->id }}"
                                    data-semester="{{ $subjectOption->semester }}"
                                    data-year-level="{{ $subjectOption->year_level }}"
                                    data-curriculum="{{ $subjectOption->curriculum }}"
                                    @selected($selectedSubjectId===(string)$subjectOption->id)
                                >{{ $subjectOption->code }} &mdash; {{ $subjectOption->name }} ({{ $subjectOption->curriculum }} Curriculum)</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p id="subjectAvailability" class="assignment-field-note">Choose a semester and year level to see the matching subjects.</p>
                @error('subject_id')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="assignment-control-group">
                <label for="instructor_department">Instructor Department / Program</label>
                <select id="instructor_department" class="input" name="instructor_department" required>
                    @foreach($departments as $department)
                        <option value="{{ $department }}" @selected($selectedDepartment===$department)>{{ $department }}</option>
                    @endforeach
                </select>
                <p id="instructorDepartmentNote" class="assignment-field-note">Choose your department to set instructor priorities directly.</p>
                @error('instructor_department')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div id="priorityAssignmentGroup" class="assignment-priority-group">
                <label>Instructor Priorities</label>
                <div class="priority-list">
                    @for($priority=1;$priority<=4;$priority++)
                        <div class="priority-row">
                            <span class="priority-label"><span class="priority-number">{{ $priority }}</span>Priority {{ $priority }}</span>
                            <div class="priority-combobox">
                                <input
                                    id="priorityInstructor{{ $priority }}"
                                    class="input priority-search"
                                    type="search"
                                    autocomplete="off"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="priorityInstructorOptions{{ $priority }}"
                                    aria-label="Search for Priority {{ $priority }} instructor"
                                    placeholder="{{ $priority===1?'Search for the primary instructor':'Search for a backup instructor' }}"
                                >
                                <select class="priority-select" name="instructor_ids[]" data-priority="{{ $priority }}" hidden aria-hidden="true" tabindex="-1">
                                    <option value="">{{ $priority===1?'Select the primary instructor':'Optional backup instructor' }}</option>
                                    @foreach($instructors as $instructor)
                                        <option
                                            value="{{ $instructor->id }}"
                                            data-department="{{ $instructor->course }}"
                                            data-instructor-name="{{ $instructor->name }}"
                                            data-employment="{{ str($instructor->employment_type??'Unspecified')->replace('_',' ')->title() }}"
                                            @selected(($selectedInstructorIds[$priority-1]??null)===$instructor->id)
                                        >{{ $instructor->name }} &middot; {{ str($instructor->employment_type??'Unspecified')->replace('_',' ')->title() }}</option>
                                    @endforeach
                                </select>
                                <div id="priorityInstructorOptions{{ $priority }}" class="priority-options" role="listbox" hidden></div>
                            </div>
                        </div>
                    @endfor
                    <p id="noDepartmentInstructors" class="priority-empty" role="status" aria-live="polite"></p>
                </div>
                <div class="priority-explanation">
                    Priority 1 receives sections first. When that instructor reaches the configured workload-hour limit or has no conflict-free time, the scheduler tries Priority 2, followed by Priority 3 and Priority 4. The same instructor cannot occupy two priority positions.
                    Instructors who cannot accept the selected subject without exceeding their workload-hour limit are hidden.
                    @if($activeAcademicYear) Current generated loads are checked against A.Y. {{ $activeAcademicYear }}. @endif
                </div>
                @error('instructor_ids')<div class="error">{{ $message }}</div>@enderror
                @error('instructor_ids.*')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <footer class="admin-profile-actions">
            <button id="cancelAssignmentForm" class="button button-secondary" type="button">Cancel</button>
            <button id="assignmentSubmitButton" class="button" type="submit" @disabled($subjectOptions->isEmpty())>Submit Assignment</button>
        </footer>
    </form>
</div>
</section>
</div>
@endpush

@push('scripts')
<script>
    (()=>{
        const modal=document.getElementById('assignmentFormModal');
        const openButtons=[...document.querySelectorAll('.assignment-form-trigger')];
        const closeButton=document.getElementById('closeAssignmentForm');
        const cancelButton=document.getElementById('cancelAssignmentForm');
        const semester=document.getElementById('semester');
        const yearLevel=document.getElementById('year_level');
        const subject=document.getElementById('subject_id');
        const department=document.getElementById('instructor_department');
        const subjectOptions=[...subject.querySelectorAll('option[data-semester]')];
        const subjectGroups=[...subject.querySelectorAll('optgroup[data-year-group]')];
        const prioritySelects=[...document.querySelectorAll('.priority-select')];
        const priorityAssignmentGroup=document.getElementById('priorityAssignmentGroup');
        const submitButton=document.getElementById('assignmentSubmitButton');
        const departmentNote=document.getElementById('instructorDepartmentNote');
        const emptyMessage=document.getElementById('noDepartmentInstructors');
        const subjectAvailability=document.getElementById('subjectAvailability');
        const assignments=@json($subjectAssignments);
        const scheduledLoads=@json($scheduledInstructorLoads);
        const assignedLoads=@json($assignedInstructorLoads);
        const instructorLimits=@json($instructorLimits);
        const localDepartment=@json($course);
        const shouldOpen=@json(request()->boolean('open_assignment_modal') || $errors->any());

        function openModal(){
            modal.hidden=false;
            document.body.classList.add('modal-open');
            window.setTimeout(()=>semester.focus({preventScroll:true}),0);
        }

        function closeModal(){
            modal.hidden=true;
            document.body.classList.remove('modal-open');
        }

        function filterSubjects(clearInvalid=true){
            let visibleCount=0;
            let selectedVisible=false;
            subjectOptions.forEach(option=>{
                const visible=option.dataset.semester===semester.value&&option.dataset.yearLevel===yearLevel.value;
                option.hidden=!visible;
                option.disabled=!visible;
                if(visible){visibleCount++;if(option.selected)selectedVisible=true;}
            });
            subjectGroups.forEach(group=>{
                group.hidden=![...group.querySelectorAll('option[data-semester]')].some(option=>!option.disabled);
            });
            if(clearInvalid&&!selectedVisible)subject.value='';
            subjectAvailability.textContent=!semester.value||!yearLevel.value
                ?'Choose a semester and year level to see the matching subjects.'
                :visibleCount?`${visibleCount} subject${visibleCount===1?'':'s'} available for the selected period.`:'No subjects are available for this semester and year level.';
        }

        function selectedPriorityIds(){
            return prioritySelects.map(select=>Number(select.value)).filter(Boolean);
        }

        function isExternalDepartment(){
            return department.value!==localDepartment;
        }

        function updateAssignmentMode(){
            const external=isExternalDepartment();
            priorityAssignmentGroup.hidden=external;
            prioritySelects.forEach((select,index)=>{
                select.disabled=external;
                select.required=false;
                const searchControl=prioritySearchControls[index];
                if(searchControl)searchControl.search.disabled=external;
                if(external){
                    select.value='';
                    if(searchControl)searchControl.search.value='';
                }
            });
            submitButton.textContent=external
                ?`Request ${department.value} Instructor`
                :'Submit Assignment';
            departmentNote.textContent=external
                ?`Submitting will notify the ${department.value} Dean, who will assign an instructor from their department.`
                :'Choose your department to set instructor priorities directly.';
        }

        function refreshInstructorOptions(){
            const assignment=assignments[subject.value]??{instructor_ids:[],workload_hours:0,semester:semester.value};
            const alreadyAssigned=assignment.instructor_ids.map(Number);
            const selectedIds=selectedPriorityIds();
            let availableCount=0;

            prioritySelects.forEach(select=>{
                const ownValue=Number(select.value||0);
                [...select.options].forEach(option=>{
                    if(!option.value)return;
                    const id=Number(option.value);
                    const correctDepartment=option.dataset.department===department.value;
                    const scheduledUnits=Number(scheduledLoads[id]?.[semester.value]??0);
                    const subjectUnits=Number(assignedLoads[id]?.[semester.value]??0);
                    const currentUnits=Math.max(scheduledUnits,subjectUnits);
                    const limit=Number(instructorLimits[id]??0);
                    const hasCapacity=currentUnits+Number(assignment.workload_hours||0)<=limit||alreadyAssigned.includes(id);
                    const duplicate=selectedIds.includes(id)&&id!==ownValue;
                    option.hidden=!correctDepartment;
                    option.disabled=!correctDepartment||!hasCapacity||duplicate;
                    option.textContent=`${option.dataset.instructorName} · ${option.dataset.employment} · ${currentUnits}/${limit} hours`;
                    if(correctDepartment&&hasCapacity&&!duplicate)availableCount++;
                });
                if(select.value&&select.selectedOptions[0]?.disabled)select.value='';
            });

            emptyMessage.style.display=availableCount===0?'block':'none';
            emptyMessage.textContent=availableCount===0
                ?'No department instructor currently has enough available workload hours for this subject.':'';
            syncPrioritySearches();
        }

        const prioritySearchControls=[...document.querySelectorAll('.priority-combobox')].map(combobox=>({
            combobox,
            search:combobox.querySelector('.priority-search'),
            select:combobox.querySelector('.priority-select'),
            options:combobox.querySelector('.priority-options'),
        }));

        function syncPrioritySearches(){
            prioritySearchControls.forEach(control=>{
                const selected=control.select.selectedOptions[0];

                if(selected?.value){
                    control.search.value=selected.textContent.trim();
                }else if(document.activeElement!==control.search){
                    control.search.value='';
                }
            });
        }

        function closePrioritySearchOptions(except=null){
            prioritySearchControls.forEach(control=>{
                if(control===except)return;
                control.options.hidden=true;
                control.search.setAttribute('aria-expanded','false');
            });
        }

        function renderPrioritySearchOptions(control, query=control.search.value){
            const searchTerm=query.trim().toLocaleLowerCase();
            const candidates=[...control.select.options].filter(option=>option.value&&!option.hidden&&!option.disabled&&option.textContent.toLocaleLowerCase().includes(searchTerm));
            control.options.replaceChildren();

            if(candidates.length===0){
                const message=document.createElement('p');
                message.className='priority-no-results';
                message.textContent='No available instructor found.';
                control.options.append(message);
            }else{
                candidates.forEach(candidate=>{
                    const option=document.createElement('button');
                    option.type='button';
                    option.className='priority-option';
                    option.setAttribute('role','option');
                    option.textContent=candidate.textContent.trim();
                    option.addEventListener('mousedown',event=>event.preventDefault());
                    option.addEventListener('click',()=>{
                        control.select.value=candidate.value;
                        control.search.value=candidate.textContent.trim();
                        control.search.setCustomValidity('');
                        refreshInstructorOptions();
                        closePrioritySearchOptions();
                    });
                    control.options.append(option);
                });
            }

            control.options.hidden=false;
            control.search.setAttribute('aria-expanded','true');
        }

        function loadSubjectAssignment(){
            const assignment=assignments[subject.value];
            if(assignment){
                department.value=assignment.department??localDepartment;
                prioritySelects.forEach((select,index)=>{select.value=String(assignment.instructor_ids[index]??'');});
            }else{
                department.value=localDepartment;
                prioritySelects.forEach(select=>{select.value='';});
            }
            updateAssignmentMode();
            refreshInstructorOptions();
        }

        semester.addEventListener('change',()=>{filterSubjects();loadSubjectAssignment();});
        yearLevel.addEventListener('change',()=>{filterSubjects();loadSubjectAssignment();});
        subject.addEventListener('change',loadSubjectAssignment);
        department.addEventListener('change',()=>{updateAssignmentMode();refreshInstructorOptions();});
        prioritySelects.forEach(select=>select.addEventListener('change',refreshInstructorOptions));
        prioritySearchControls.forEach(control=>{
            control.search.addEventListener('focus',()=>{
                closePrioritySearchOptions(control);
                if(control.select.value)control.search.select();
                renderPrioritySearchOptions(control,control.select.value?'':control.search.value);
            });
            control.search.addEventListener('input',()=>{
                control.select.value='';
                control.search.setCustomValidity('');
                refreshInstructorOptions();
                renderPrioritySearchOptions(control);
            });
            control.search.addEventListener('keydown',event=>{
                if(event.key==='Escape')closePrioritySearchOptions();
            });
            control.search.addEventListener('blur',()=>window.setTimeout(()=>closePrioritySearchOptions(),120));
        });
        document.addEventListener('click',event=>{
            if(!event.target.closest('.priority-combobox'))closePrioritySearchOptions();
        });
        document.getElementById('priorityAssignmentForm').addEventListener('submit',event=>{
            const primary=prioritySearchControls[0];
            if(!isExternalDepartment()&&!primary.select.value){
                event.preventDefault();
                primary.search.setCustomValidity('Select a Priority 1 instructor from the search results.');
                primary.search.reportValidity();
                primary.search.focus();
                renderPrioritySearchOptions(primary);
            }
        });
        openButtons.forEach(button=>button.addEventListener('click',openModal));
        closeButton.addEventListener('click',closeModal);
        cancelButton.addEventListener('click',closeModal);
        modal.addEventListener('click',event=>{if(event.target===modal)closeModal();});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)closeModal();});

        filterSubjects(false);
        updateAssignmentMode();
        refreshInstructorOptions();
        if(shouldOpen)openModal();
    })();
</script>
@endpush
