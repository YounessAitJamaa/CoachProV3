<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation réservation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center">

<div class="max-w-lg w-full bg-slate-800/50 border border-slate-700 rounded-xl p-6">

    <h1 class="text-2xl font-bold mb-6 text-center">
        Confirmation de réservation
    </h1>

    <div class="space-y-4 mb-6">
        <div class="flex justify-between border-b border-slate-700/50 pb-2">
            <span class="text-slate-400">Coach</span>
            <span class="font-semibold"><?= htmlspecialchars($dispo['coach_prenom'].' '.$dispo['coach_nom']) ?></span>
        </div>

        <div class="flex justify-between border-b border-slate-700/50 pb-2">
            <span class="text-slate-400">Date</span>
            <span class="font-semibold">
                <?= date('d/m/Y', strtotime($dispo['date_disponibilite'])) ?>
            </span>
        </div>

        <div class="flex justify-between">
            <span class="text-slate-400">Heure</span>
            <span class="font-semibold text-orange-500">
                <?= date('H:i', strtotime($dispo['heure_debut'])) ?> 
                → 
                <?= date('H:i', strtotime($dispo['heure_fin'])) ?>
            </span>
        </div>
    </div>

    <form method="POST" action="<?= URLROOT ?>/sportif/reserver/confirmer" class="flex gap-4">
        <input type="hidden" name="disp_id" value="<?= $disp_id ?>">

        <a href="<?= URLROOT ?>/sportif/coach-details?id=<?= $dispo['id_coach'] ?>"
           class="flex-1 text-center border border-slate-600 rounded-lg py-2 hover:bg-slate-700 transition-colors">
            Annuler
        </a>

        <button type="submit"
                name="submit"
                class="flex-1 bg-orange-500 hover:bg-orange-400 text-slate-900 font-semibold rounded-lg py-2 transition-transform hover:scale-105">
            Confirmer
        </button>
    </form>

</div>

</body>
</html>