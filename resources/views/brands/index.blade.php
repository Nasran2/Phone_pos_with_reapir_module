<x-layouts::app :title="__('Brand Management')">
    <!-- SweetAlert2 CDN for modern popup alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Product Brands</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $brands->count() }} Brands
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('brands.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Brand</span>
            </button>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <!-- Success & Error Alert Messages -->
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Brands Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden max-w-6xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Brand Logo</th>
                            <th class="px-6 py-4">Brand Name</th>
                            <th class="px-6 py-4">Slug (URL)</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Products Count</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($brands as $brand)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                @if($brand->image)
                                <img src="{{ asset('storage/' . $brand->image) }}" class="h-10 w-10 object-contain rounded-md border border-slate-200" alt="{{ $brand->name }} Logo">
                                @else
                                <div class="h-10 w-10 bg-slate-100 text-slate-400 font-extrabold flex items-center justify-center rounded-md border border-slate-200 text-xs select-none">
                                    {{ substr($brand->name, 0, 2) }}
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                {{ $brand->name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-[11px] text-slate-500">
                                {{ $brand->slug }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">
                                {{ $brand->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px]">
                                    {{ $brand->products_count }} Products
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->hasPermission('brands.edit'))
                                    <button 
                                        onclick="openEditModal({{ json_encode($brand) }})"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                        title="Edit Brand"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('brands.delete'))
                                    <button 
                                        onclick="confirmDelete({{ $brand->id }}, '{{ $brand->name }}')"
                                        class="p-1.5 text-slate-400 hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                        title="Delete Brand"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <form id="delete-form-{{ $brand->id }}" method="POST" action="{{ route('brands.destroy', $brand->id) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No brands registered yet.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BRAND CREATE / EDIT MODAL -->
    <div id="brandModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-slate-800">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850" id="modalTitle">Create Brand</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <!-- Multipart Form for file uploading -->
            <form id="brandForm" method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Brand Name *</label>
                    <input type="text" name="name" id="brand_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="e.g. Apple">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Short Description</label>
                    <textarea name="description" id="brand_description" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-850" placeholder="Describe the brand..."></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Brand Logo (Image File)</label>
                    <div class="flex items-center gap-4">
                        <input type="file" name="logo" id="brand_logo" onchange="previewLogo(this)" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                        <img id="logo_preview" src="#" class="hidden h-12 w-12 object-contain rounded-md border border-slate-250 bg-slate-50" alt="Logo preview">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Brand</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripting -->
    <script>
        function openModal() {
            const modal = document.getElementById('brandModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('brandModal');
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Create Product Brand';
            document.getElementById('brandForm').action = "{{ route('brands.store') }}";
            document.getElementById('formMethod').value = 'POST';
            
            document.getElementById('brand_name').value = '';
            document.getElementById('brand_description').value = '';
            document.getElementById('brand_logo').value = '';
            
            const preview = document.getElementById('logo_preview');
            preview.src = '#';
            preview.classList.add('hidden');
            
            openModal();
        }

        function openEditModal(brand) {
            document.getElementById('modalTitle').innerText = `Edit Brand: ${brand.name}`;
            document.getElementById('brandForm').action = `/brands/${brand.id}`;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('brand_name').value = brand.name;
            document.getElementById('brand_description').value = brand.description || '';
            document.getElementById('brand_logo').value = '';
            
            const preview = document.getElementById('logo_preview');
            if (brand.image) {
                preview.src = `/storage/${brand.image}`;
                preview.classList.remove('hidden');
            } else {
                preview.src = '#';
                preview.classList.add('hidden');
            }
            
            openModal();
        }

        function previewLogo(input) {
            const preview = document.getElementById('logo_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Brand?',
                text: `Are you sure you want to soft delete the brand "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            });
        }
    </script>
</x-layouts::app>
