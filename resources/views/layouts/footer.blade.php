<!-- resources/views/partials/footer.blade.php -->

<!-- Footer -->
<footer class="footer relative pt-1 sm:pt-5 md:pt-10 mx-auto bg-gray-600">
    <!-- Container Grid -->
    <div class="max-w-screen-lg xl:max-w-screen-xl mx-auto px-4 sm:px-6 md:px-8 mt-8 border-b-0">
        <!-- Grid -->
        <ul
            class="text-center Footer_nav__2rFid text-xs md:text-sm font-medium pb-6 grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-y-6 sm:gap-y-10">
            <!-- Informationen -->
            <li class="space-y-2 sm:space-y-3 row-span-2 px-4">
                <p class="text-xs font-semibold tracking-wide text-gray-200 uppercase">Informationen</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('devices.overview') }}">
                            <img src="{{ asset('img/loan-logo.png') }}" alt="Logo"
                                class="w-[200px] h-auto mt-6 mb-6 mx-auto" style="width: 210px;">
                        </a>
                        <p class="text-gray-300 transition-colors duration-200 font-normal">Version 1.2: 08/2025</p>
                        <p class="text-gray-300 transition-colors duration-200 font-normal mt-2"><em><strong>LOAN | Das
                                    System zur Geräteausleihe</strong></em> ist eine Plattform von <a
                                href="https://github.com/dusanvin" class="hover:underline" target="_blank"
                                rel="noopener">Vincent Dusanek</a>, um Gegenstände zu inventarisieren.</p>
                        <p class="text-gray-300 transition-colors duration-200 font-normal mt-2 mr-2 mb-6">
                            <em><strong>LOAN | Das System zur Geräteausleihe</strong></em> von <a
                                href="https://github.com/dusanvin" class="hover:underline" target="_blank"
                                rel="noopener"><strong>Vincent Dusanek</strong></a>, 2024. MIT-Lizenz.</p>
                        <div
                            class="inline-flex items-stretch rounded-md overflow-hidden border border-gray-300 text-gray-300 text-xs">
                            <!-- Star-Button -->
                            <a href="https://github.com/dusanvin/LOAN" target="_blank" rel="noopener"
                                class="btn inline-flex items-center px-2 py-1 font-medium hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-indigo-500">
                                <!-- GitHub Icon -->
                                <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"
                                    class="octicon octicon-mark-github">
                                    <path fill="currentColor"
                                        d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82A6.97 6.97 0 0 0 8 4.96a6.97 6.97 0 0 0-2 .27c-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z">
                                    </path>
                                </svg>
                                <span class="ml-1">Sterne</span>
                            </a>

                            <!-- Counter -->
                            <a href="https://github.com/dusanvin/LOAN/stargazers" target="_blank" rel="noopener"
                                aria-label="GitHub Sterne für LOAN" id="star-count"
                                class="social-count inline-flex items-center justify-center min-w-[3.5rem] px-2.5 py-1.5 text-xs font-medium text-gray-300 border-l border-gray-300">
                                …
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!-- Organisation -->
            <li class="space-y-2 sm:space-y-3 row-span-2 px-4">
                <p class="text-xs font-semibold tracking-wide text-gray-200 uppercase">Service</p>
                <ul class="space-y-1">
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="{{ url('/product') }}" target="_blank" rel="noopener">Produkt</a>
                    </li>
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="https://github.com/dusanvin/LOAN" target="_blank" rel="noopener">GitHub</a>
                    </li>
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="{{ url('/logs') }}" target="_blank" rel="noopener">Log</a>
                    </li>
                </ul>
            </li>
            <!-- Über -->
            <li class="space-y-2 sm:space-y-3 row-span-2 px-4">
                <p class="text-xs font-semibold tracking-wide text-gray-200 uppercase">Über</p>
                <ul class="space-y-1">
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="{{ url('/impressum') }}" target="_blank" rel="noopener">Impressum</a>
                    </li>
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="{{ url('/datenschutz') }}" target="_blank" rel="noopener">Datenschutz</a>
                    </li>
                </ul>
            </li>
            <!-- Allgemein -->
            <li class="space-y-2 sm:space-y-3 row-span-2 px-4">
                <p class="text-xs font-semibold tracking-wide text-gray-200 uppercase">Allgemein</p>
                <ul class="space-y-1">
                    <li>
                        <a class="text-gray-300 hover:text-white transition-colors duration-200 font-normal"
                            href="mailto:vincent.dusanek@gmail.com">Kontakt</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <!-- Container Grid -->
</footer>
<!-- Footer -->

<!-- Script -->
<script>
    fetch("https://api.github.com/repos/dusanvin/LOAN")
        .then(r => r.json())
        .then(data => {
            document.getElementById("star-count").textContent =
                data.stargazers_count.toLocaleString();
        })
        .catch(() => {
            document.getElementById("star-count").textContent = "—";
        });
</script>
