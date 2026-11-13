<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-blue-950 dark:text-white">Quick Help</h2>
            <p class="mt-1 text-sm text-blue-600 dark:text-blue-300">Simple guide for scanning, checking, and reporting</p>
        </div>
    </x-slot>

    <div class="py-5 sm:py-8" x-data="{ language: 'english' }">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="rounded-md border border-blue-100 bg-white p-4 shadow-sm sm:p-5 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">Guard guide</p>
                        <h3 class="mt-1 text-lg font-semibold text-blue-950 dark:text-white">What do you need help with?</h3>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">Choose a language. The steps are the same in every tab.</p>
                    </div>
                    <a href="{{ route('patrol.scan') }}" class="inline-flex h-10 items-center justify-center rounded-md border border-blue-200 px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-600 dark:text-blue-200 dark:hover:bg-slate-800">
                        Back to Scan Checkpoint
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-1 rounded-md bg-blue-50/70 p-1 dark:bg-slate-800" role="tablist" aria-label="Quick help language">
                    <button type="button" role="tab" :aria-selected="language === 'english'" class="rounded-md px-2 py-2 text-sm font-semibold transition" :class="language === 'english' ? 'bg-white text-blue-800 shadow-sm dark:bg-slate-700 dark:text-blue-100' : 'text-slate-600 hover:bg-white/70 dark:text-slate-300 dark:hover:bg-slate-700/70'" @click="language = 'english'">English</button>
                    <button type="button" role="tab" :aria-selected="language === 'tagalog'" class="rounded-md px-2 py-2 text-sm font-semibold transition" :class="language === 'tagalog' ? 'bg-white text-blue-800 shadow-sm dark:bg-slate-700 dark:text-blue-100' : 'text-slate-600 hover:bg-white/70 dark:text-slate-300 dark:hover:bg-slate-700/70'" @click="language = 'tagalog'">Tagalog</button>
                    <button type="button" role="tab" :aria-selected="language === 'bisaya'" class="rounded-md px-2 py-2 text-sm font-semibold transition" :class="language === 'bisaya' ? 'bg-white text-blue-800 shadow-sm dark:bg-slate-700 dark:text-blue-100' : 'text-slate-600 hover:bg-white/70 dark:text-slate-300 dark:hover:bg-slate-700/70'" @click="language = 'bisaya'">Bisaya</button>
                </div>
            </section>

            <section x-show="language === 'english'" x-cloak class="grid gap-4 md:grid-cols-2" role="tabpanel" aria-label="English quick help">
                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">1. Start a patrol</h3>
                    <ol class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <li><strong class="text-slate-900 dark:text-white">Turn on the device.</strong> Turn on the inline switch and wait for the LCD to show the Wi-Fi connection and RFID ready message.</li>
                        <li><strong class="text-slate-900 dark:text-white">Scan once.</strong> Tap the registered RFID card on the reader and wait for the response. Do not scan repeatedly.</li>
                        <li><strong class="text-slate-900 dark:text-white">Take the area selfie.</strong> Capture the guard and the checkpoint/device area after a successful scan.</li>
                        <li><strong class="text-slate-900 dark:text-white">Complete the checklist.</strong> Inspect each item, then tap <em>Checked</em> after verifying it.</li>
                    </ol>
                </article>

                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">2. Report an incident</h3>
                    <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p><strong class="text-slate-900 dark:text-white">Incident near the checkpoint:</strong> use the incident form and attach up to two incident photos.</p>
                        <p><strong class="text-slate-900 dark:text-white">Incident seen earlier or far away:</strong> use <em>Take and Save Evidence Photo</em>. The photo keeps its capture time and GPS, and can be attached later without scanning again.</p>
                        <p>Select the category and priority, write a clear description, attach the required photos, and submit the report.</p>
                    </div>
                </article>

                <article class="rounded-md border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-100">3. Finish and confirm</h3>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-emerald-950/80 dark:text-emerald-100/80">
                        <li>Submit the patrol record only after the selfie and checklist are complete.</li>
                        <li>Wait for the success message before leaving the page.</li>
                        <li>Check <strong>My Patrol Logs</strong> or <strong>My Evidence</strong> if you need to review a submission.</li>
                        <li>After scanning, turn off the device switch according to the local instruction to reduce heat. The next guard turns it on and waits for Wi-Fi.</li>
                    </ul>
                </article>

                <article class="rounded-md border border-amber-100 bg-amber-50/60 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <h3 class="text-base font-bold text-amber-950 dark:text-amber-100">If something does not work</h3>
                    <p class="mt-3 text-sm leading-6 text-amber-950/80 dark:text-amber-100/80">Wait for the LCD and Wi-Fi message, check that you are using the correct card, and avoid repeated scans. If the problem continues, record what happened and inform the supervisor.</p>
                </article>
            </section>

            <section x-show="language === 'tagalog'" x-cloak class="grid gap-4 md:grid-cols-2" role="tabpanel" aria-label="Tagalog quick help">
                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">1. Simulan ang patrol</h3>
                    <ol class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <li><strong class="text-slate-900 dark:text-white">Buksan ang device.</strong> I-on ang inline switch at hintaying lumabas sa LCD ang Wi-Fi connection at RFID ready message.</li>
                        <li><strong class="text-slate-900 dark:text-white">Isang scan lang.</strong> I-tap ang registered RFID card sa reader at hintayin ang response. Huwag paulit-ulit na mag-scan.</li>
                        <li><strong class="text-slate-900 dark:text-white">Kunan ng area selfie.</strong> Isama sa larawan ang guard at ang checkpoint/device pagkatapos ng successful scan.</li>
                        <li><strong class="text-slate-900 dark:text-white">Kumpletuhin ang checklist.</strong> Suriin ang bawat item, saka pindutin ang <em>Checked</em> pagkatapos itong ma-verify.</li>
                    </ol>
                </article>

                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">2. Mag-report ng incident</h3>
                    <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p><strong class="text-slate-900 dark:text-white">Kung malapit sa checkpoint:</strong> gamitin ang incident form at mag-attach ng hanggang dalawang incident photos.</p>
                        <p><strong class="text-slate-900 dark:text-white">Kung nakita na mas maaga o malayo sa checkpoint:</strong> gamitin ang <em>Take and Save Evidence Photo</em>. Nananatili sa larawan ang oras at GPS nito at maaari itong i-attach mamaya nang hindi na muling mag-scan.</p>
                        <p>Piliin ang category at priority, magsulat ng malinaw na description, mag-attach ng kailangang photos, at i-submit ang report.</p>
                    </div>
                </article>

                <article class="rounded-md border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-100">3. Tapusin at i-confirm</h3>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-emerald-950/80 dark:text-emerald-100/80">
                        <li>I-submit ang patrol record kapag kumpleto na ang selfie at checklist.</li>
                        <li>Hintayin ang success message bago umalis sa page.</li>
                        <li>Buksan ang <strong>My Patrol Logs</strong> o <strong>My Evidence</strong> para tingnan ang submission.</li>
                        <li>Pagkatapos mag-scan, i-off ang device switch ayon sa local instruction para mabawasan ang init. Ang susunod na guard ang mag-o-on nito at maghihintay ng Wi-Fi.</li>
                    </ul>
                </article>

                <article class="rounded-md border border-amber-100 bg-amber-50/60 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <h3 class="text-base font-bold text-amber-950 dark:text-amber-100">Kung may problema</h3>
                    <p class="mt-3 text-sm leading-6 text-amber-950/80 dark:text-amber-100/80">Hintayin ang LCD at Wi-Fi message, siguraduhing tama ang RFID card, at huwag paulit-ulit na mag-scan. Kung hindi pa rin gumana, tandaan ang nangyari at ipaalam sa supervisor.</p>
                </article>
            </section>

            <section x-show="language === 'bisaya'" x-cloak class="grid gap-4 md:grid-cols-2" role="tabpanel" aria-label="Bisaya quick help">
                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">1. Pagsugod sa patrol</h3>
                    <ol class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <li><strong class="text-slate-900 dark:text-white">I-on ang device.</strong> I-on ang inline switch ug hulata nga mogawas sa LCD ang Wi-Fi connection ug RFID ready message.</li>
                        <li><strong class="text-slate-900 dark:text-white">Usa ra ka scan.</strong> I-tap ang registered RFID card sa reader ug hulata ang response. Ayaw sige og scan.</li>
                        <li><strong class="text-slate-900 dark:text-white">Pagkuha og area selfie.</strong> Apila sa picture ang guard ug ang checkpoint/device human sa successful scan.</li>
                        <li><strong class="text-slate-900 dark:text-white">Kompletuhon ang checklist.</strong> Susiha ang matag item, unya i-tap ang <em>Checked</em> human nimo ma-verify.</li>
                    </ol>
                </article>

                <article class="rounded-md border border-blue-100 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-blue-950 dark:text-white">2. Pag-report og incident</h3>
                    <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p><strong class="text-slate-900 dark:text-white">Kung duol sa checkpoint:</strong> gamita ang incident form ug i-attach ang hangtod duha ka incident photos.</p>
                        <p><strong class="text-slate-900 dark:text-white">Kung nakita na daan o layo sa checkpoint:</strong> gamita ang <em>Take and Save Evidence Photo</em>. Masave ang oras ug GPS sa pagkuha sa picture, ug ma-attach kini unya nga dili na mag-scan pag-usab.</p>
                        <p>Pilia ang category ug priority, isulat ang klaro nga description, i-attach ang kinahanglan nga photos, ug i-submit ang report.</p>
                    </div>
                </article>

                <article class="rounded-md border border-emerald-100 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-100">3. Pagtapos ug pag-confirm</h3>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-emerald-950/80 dark:text-emerald-100/80">
                        <li>I-submit ang patrol record kung kompleto na ang selfie ug checklist.</li>
                        <li>Hulata ang success message una mobiya sa page.</li>
                        <li>Tan-awa ang <strong>My Patrol Logs</strong> o <strong>My Evidence</strong> kung gusto nimo i-check ang submission.</li>
                        <li>Human sa scan, i-off ang device switch sumala sa local instruction aron dili kaayo mo-init. Ang sunod nga guard ang mo-on niini ug maghulat sa Wi-Fi.</li>
                    </ul>
                </article>

                <article class="rounded-md border border-amber-100 bg-amber-50/60 p-4 shadow-sm dark:border-slate-700 dark:bg-amber-950/20">
                    <h3 class="text-base font-bold text-amber-950 dark:text-amber-100">Kung naay problema</h3>
                    <p class="mt-3 text-sm leading-6 text-amber-950/80 dark:text-amber-100/80">Hulata ang LCD ug Wi-Fi message, siguroa nga sakto ang RFID card, ug ayaw sige og scan. Kung dili gihapon molihok, hinumdumi unsay nahitabo ug sultihi ang supervisor.</p>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
