<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to application/json for all API requests
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        // Check if request has our explicit JSON handling header
        $forceJsonHandling = $request->header('X-Handle-As-Json') === 'true';

        // Get the response
        $response = $next($request);

        // Check for HTML content in response
        $content = $response->getContent();
        $isHtml = false;

        // Check content type header
        if ($response->headers->has('Content-Type')) {
            $contentType = $response->headers->get('Content-Type');
            $isHtml = str_contains($contentType, 'text/html');
        }

        // Also check content for HTML patterns - be very aggressive with detection
        if (is_string($content)) {
            $isHtml = $isHtml ||
                str_contains($content, '<!DOCTYPE html>') ||
                str_contains($content, '<html') ||
                str_contains($content, '<head>') ||
                str_contains($content, '<body') ||
                str_contains($content, '<title>Laravel</title>');
        }

        // Convert any HTML/error responses to JSON - always for API routes
        if ($isHtml || ($forceJsonHandling && !str_contains($response->headers->get('Content-Type', ''), 'application/json'))) {
            $statusCode = $response->getStatusCode();
            $message = "The server returned HTML instead of the expected JSON response";

            // For HTTP 500 errors, be more explicit about server error
            if ($statusCode >= 500) {
                $message = "Server error occurred. The application encountered an internal error.";
            }

            // For HTTP 404 errors, indicate resource not found
            if ($statusCode == 404) {
                $message = "The requested resource was not found.";
            }

            // Create JSON response that always works
            $jsonResponse = new JsonResponse([
                'success' => false,
                'error' => 'HTML response was converted to JSON',
                'message' => $message,
                'status_code' => $statusCode,
                'html_preview' => substr($content, 0, 200),
            ], 500);

            return $jsonResponse;
        }

        // Ensure Content-Type is application/json for all API responses
        if (
            !$response->headers->has('Content-Type') ||
            !str_contains($response->headers->get('Content-Type'), 'application/json')
        ) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}
