<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class PdfController extends Controller
{
    /**
     * Serve the PDF attachment for an idea
     */
    public function download($slug)
    {
        // Find the idea by slug
        $idea = Idea::where('slug', $slug)->firstOrFail();

        // Check if user owns this idea
        if ($idea->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if attachment exists
        if (!$idea->attachment) {
            abort(404, 'PDF not found');
        }

        // Prepare binary content and headers
        $content = $idea->attachment;
        $length = strlen($content);

        // Determine mime type: prefer stored value, but if it's missing or generic
        // and the content looks like a PDF (starts with %PDF), force application/pdf
        $storedMime = $idea->attachment_mime;
        $mime = $storedMime;
        if (empty($mime) || $mime === 'application/octet-stream') {
            // Check for PDF magic bytes
            $prefix = substr($content, 0, 4);
            if ($prefix === '%PDF') {
                $mime = 'application/pdf';
            } else {
                // fallback to stored mime or generic binary
                $mime = $storedMime ?? 'application/octet-stream';
            }
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . ($idea->attachment_filename ?? 'idea.pdf') . '"',
            'Content-Length' => $length,
            // Allow browsers to request byte ranges (helpful for large PDFs)
            'Accept-Ranges' => 'bytes',
            // Cache for a short while; browsers may reuse the response for the iframe
            'Cache-Control' => 'public, max-age=604800, must-revalidate',
        ];

        return Response::make($content, 200, $headers);
    }
}