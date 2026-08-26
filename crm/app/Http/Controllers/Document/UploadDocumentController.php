<?php

namespace App\Http\Controllers\Document;

use App\Events\DocumentUploaded;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentOcrJob;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class UploadDocumentController extends Controller
{
    public function __invoke(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'document_type' => ['nullable', 'string', 'max:50'],
        ]);

        if (! $request->hasFile('file') || ! $request->file('file')->isValid()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'File not uploaded.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()->withErrors(['file' => 'File not uploaded.']);
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $storedPath = $file->store('documents/' . $user->id, 'local');

        $document = Document::create([
            'user_id' => $user->id,
            'file_path' => $storedPath,
            'original_filename' => $originalFilename,
            'document_type' => $request->input('document_type', 'trudova_auto'),
            'status' => 'pending',
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'document_uploaded',
            'entity_type' => 'Document',
            'entity_id' => $document->id,
            'payload' => ['original_filename' => $originalFilename],
            'ip_address' => $request->ip(),
        ]);

        // Dispatch domain event for notification handling
        event(new DocumentUploaded($document));

        // Dispatch queue job for asynchronous processing
        if (app()->environment('testing')) {
            ProcessDocumentOcrJob::dispatchSync($document);
        } else {
            ProcessDocumentOcrJob::dispatch($document);
        }

        $document->refresh();
        /** @var \App\Models\RecognizedDocument|null $recognizedDoc */
        $recognizedDoc = $document->recognizedDocument;

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'document' => $document,
                    'recognized' => $recognizedDoc,
                ],
            ], Response::HTTP_CREATED);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("Document ':file' uploaded successfully and queued for processing.", ['file' => $originalFilename]),
        ]);

        return redirect()->back();
    }
}
