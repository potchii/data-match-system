<?php

namespace App\Http\Traits;

use App\Models\ColumnMappingTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait for authorizing template access
 * 
 * Provides reusable methods for checking if the authenticated user
 * has permission to access a specific template.
 */
trait AuthorizesTemplates
{
    /**
     * Find template and verify user ownership
     *
     * @param int $templateId
     * @param bool $withFields Whether to eager load fields relationship
     * @return ColumnMappingTemplate|null
     */
    protected function findAuthorizedTemplate(int $templateId, bool $withFields = false): ?ColumnMappingTemplate
    {
        $query = ColumnMappingTemplate::where('id', $templateId)
            ->where('user_id', Auth::id());
        
        if ($withFields) {
            $query->with('fields');
        }
        
        $template = $query->first();
        
        if (!$template) {
            Log::warning('Unauthorized template access attempt', [
                'template_id' => $templateId,
                'user_id' => Auth::id(),
                'user' => Auth::user()->name ?? 'unknown',
            ]);
        }
        
        return $template;
    }

    /**
     * Return unauthorized JSON response
     *
     * @return JsonResponse
     */
    protected function unauthorizedTemplateResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Template not found or you do not have permission to access it.',
        ], 404);
    }
}
