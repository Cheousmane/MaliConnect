<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreReportRequest;
use App\Models\ProviderProfile;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;

/**
 * Dépôt d'un signalement depuis une fiche publique.
 */
class ReportController extends Controller
{
    /**
     * Enregistre un signalement visant un profil professionnel.
     */
    public function store(StoreReportRequest $request, ProviderProfile $profile): RedirectResponse
    {
        abort_unless($request->user()->can('reports.create'), 403);
        abort_unless($profile->isPubliclyVisible(), 404);

        $alreadyReported = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reportable_type', $profile->getMorphClass())
            ->where('reportable_id', $profile->id)
            ->open()
            ->exists();

        if ($alreadyReported) {
            return back()->with('error', 'Vous avez déjà un signalement en cours pour ce profil.');
        }

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $profile->getMorphClass(),
            'reportable_id' => $profile->id,
            'reason' => $request->enum('reason', ReportReason::class),
            'description' => $request->validated('description'),
            'status' => ReportStatus::Pending,
        ]);

        return back()->with('success', 'Votre signalement a été transmis à l’équipe de modération.');
    }
}
