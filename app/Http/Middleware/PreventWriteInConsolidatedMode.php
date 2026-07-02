<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventWriteInConsolidatedMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow login and set-company routes to proceed regardless of the active company
        if ($request->is('login') || $request->is('set-company') || $request->routeIs('login') || $request->routeIs('set-company')) {
            return $next($request);
        }

        if (session('company_id') === 'all') {
            // Check if it's a write action (POST, PUT, PATCH, DELETE) or accessing a create/edit form
            if (
                $request->isMethod('post') || 
                $request->isMethod('put') || 
                $request->isMethod('patch') || 
                $request->isMethod('delete') || 
                $request->is('*/create') || 
                $request->is('*/edit') ||
                $request->is('*/create/*') ||
                $request->is('*/edit/*')
            ) {
                if ($request->expectsJson() || $request->header('X-Inertia')) {
                    return back()->withErrors([
                        'error' => 'Silakan pilih perusahaan spesifik terlebih dahulu untuk melakukan transaksi.'
                    ]);
                }
                
                return redirect()->back()->withErrors([
                    'error' => 'Silakan pilih perusahaan spesifik terlebih dahulu untuk melakukan transaksi.'
                ]);
            }
        }

        return $next($request);
    }
}
