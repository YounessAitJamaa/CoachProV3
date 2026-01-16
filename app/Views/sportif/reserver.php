<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver une séance - CoachPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen text-slate-200">

    <?php include '../includes/header.php' ?>

    <div class="container mx-auto px-4 sm:px-6 py-8">
        <div class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2">
                Réserver une <span class="text-orange-500">séance</span>
            </h1>
            <p class="text-slate-400">Trouvez le coach idéal et réservez votre créneau en un clic.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
           <aside class="w-full lg:w-72 flex-shrink-0">
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
                    <h2 class="text-white font-bold mb-4">Filtrer par sport</h2>
                    
                    <form action="/CoachProV3/public/sportif/reserver" method="GET">
                        <select name="discipline" 
                                onchange="this.form.submit()" 
                                class="w-full bg-slate-900 border border-slate-700 text-white rounded-lg p-3 outline-none focus:ring-2 focus:ring-orange-500">
                            
                            <option value="">Toutes les disciplines</option>
                            
                            <?php foreach($disciplines as $disc): ?>
                                <option value="<?= $disc['id_discipline'] ?>" 
                                    <?= ($selectedDiscipline == $disc['id_discipline']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($disc['nom_discipline']) ?>
                                </option>
                            <?php endforeach; ?>
                            
                        </select>
                    </form>
                    
                    <?php if($selectedDiscipline): ?>
                        <a href="/CoachProV3/public/sportif/reserver" class="block text-center mt-4 text-sm text-slate-400 hover:text-orange-500">
                            ✖ Effacer le filtre
                        </a>
                    <?php endif; ?>
                </div>
            </aside>

            <main class="flex-1">
                <?php if (empty($coaches)): ?>
                    <div class="bg-slate-800/30 border-2 border-dashed border-slate-700 rounded-2xl py-20 text-center">
                        <p class="text-slate-500 text-lg">Aucun coach disponible pour cette discipline.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($coaches as $coach): ?>
                            <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-6 hover:border-orange-500/50 transition-all duration-300 group">
                                <div class="flex items-center gap-5 mb-6">
                                    <div class="relative">
                                        <img src="<?= htmlspecialchars($coach['photo'] ?? '/assets/img/default.png') ?>" 
                                             alt="Coach" 
                                             class="w-20 h-20 rounded-2xl object-cover border-2 border-slate-700 group-hover:border-orange-500 transition-colors">
                                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-4 border-slate-800 rounded-full"></div>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h3 class="text-xl font-bold text-white truncate"><?= htmlspecialchars($coach['prenom'] . ' ' . $coach['nom']) ?></h3>
                                        <div class="flex items-center gap-2 text-orange-500 text-sm font-medium">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            <span><?= number_format($coach['moyenne_note'] ?? 0, 1) ?> (<?= $coach['total_avis'] ?> avis)</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3 mb-6 text-sm text-slate-400">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span><?= $coach['experience'] ?> ans d'expérience</span>
                                    </div>
                                </div>

                                <a href="/CoachProV3/public/sportif/coach-details?id=<?= $coach['id_coach'] ?>" 
                                   class="w-full block bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl text-center transition-all active:scale-95 shadow-lg shadow-orange-500/20">
                                    Voir disponibilités
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

</body>
</html>