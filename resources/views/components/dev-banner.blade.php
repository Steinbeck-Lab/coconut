@if(config('app.env') === 'development')
    <div class="px-4 py-2 text-center font-bold relative z-50" 
         style="background-color: #facc15 !important; color: #000000 !important; border-bottom: 2px solid #eab308 !important;">
        <div class="flex items-center justify-center space-x-2">
            <span class="text-sm font-semibold" style="color: #000000 !important;"> DEMO SITE WARNING: Please be aware that this is a demo/test server for COCONUT and don't upload or save any sensitive data. For real data please visit coconut.naturalproducts.net. </span>
        </div>
    </div>
@endif