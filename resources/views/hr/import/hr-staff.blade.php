@extends('layouts.app')

@section('title', 'Import HR Staff')

@section('subtitle', 'Bulk import staff from CSV file')

@section('actions')
<a href="{{ route('hr.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
    <i class="fas fa-arrow-left mr-2"></i> Back to HR
</a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Import Form -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Upload CSV File</h3>
                
                <form action="{{ route('hr.import.hr-staff') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="space-y-4">
                        <!-- File Upload -->
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                                CSV File *
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-csv text-3xl text-gray-400 mx-auto"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="csv_file" name="csv_file" type="file" class="sr-only" accept=".csv,.txt" required>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">CSV, TXT up to 10MB</p>
                                </div>
                            </div>
                            @error('csv_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Update Existing -->
                        <div class="flex items-center">
                            <input id="update_existing" name="update_existing" type="checkbox" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                            <label for="update_existing" class="ml-2 block text-sm text-gray-900">
                                Update existing records
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-upload mr-2"></i> Import Staff
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- CSV Format Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">CSV Format Requirements</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-blue-800 mb-2">Required Columns</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><code>staff_id</code> - Unique staff identifier</li>
                            <li><code>name</code> - Full name of staff member</li>
                            <li><code>phone</code> - Phone number</li>
                            <li><code>status</code> - active/inactive</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-blue-800 mb-2">Optional Columns</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><code>dept</code> - Department</li>
                            <li><code>email</code> - Email address</li>
                        </ul>
                    </div>
                </div>

                <!-- Sample CSV -->
                <div class="mt-4">
                    <h4 class="font-medium text-blue-800 mb-2">Sample CSV Format</h4>
                    <div class="bg-white border border-blue-200 rounded-md p-3 text-sm">
                        <code class="text-blue-700">
staff_id,name,phone,dept,email,status<br>
STU001,John Doe,0234567890,IT,john.doe@stu.edu.gh,active<br>
STU002,Jane Smith,0234567891,HR,jane.smith@stu.edu.gh,active<br>
STU003,Bob Johnson,0234567892,Finance,,inactive
                        </code>
                    </div>
                </div>
            </div>

            <!-- Import Errors -->
            @if(session('import_errors'))
            <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-red-900 mb-4">Import Errors</h3>
                <div class="text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
