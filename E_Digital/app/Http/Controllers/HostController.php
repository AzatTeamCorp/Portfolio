<?php
namespace App\Http\Controllers;

use App\Models\Show;
use Inertia\Inertia;
use Inertia\Response;
use App\Events\ShowSlide;
use App\Models\SlideType;
use App\Models\SlideTheme;
use App\Events\FinishSlide;
use App\Events\ShowStarted;
use Illuminate\Support\Str;
use App\Models\Presentation;
use App\Traits\ImageService;
use Illuminate\Http\Request;
use App\Events\StopPresenting;
use App\Events\PrepareForSlide;
use App\Events\StartPresenting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HostController extends Controller
{
    use ImageService;

    public function showTemplatesPage(Request $request)
    {
        return Inertia::render('User/Templates');
    }

    public function showEditorPage($presentation_id)
    {
        $presentation = Presentation::findOrFail($presentation_id);
        
        if (Gate::denies('own-presentation', $presentation_id)) {
            return abort(404);
        }
        
        $show = $presentation->show();
        if ($show) {
            $show->state = 'inactive';
            $show->save();
            broadcast(new StopPresenting($show->id));
        }
        $presentation = Presentation::with('slides.type')->findOrFail($presentation_id);
        return Inertia::render('User/Editor', [
            'presentation' => $presentation,
            'slideTypes' => SlideType::all(),
            'slideThemes' => SlideTheme::all(),
            'slide_id' => $presentation->slides->first()->id,
        ]);
    }

    public function newPresentation(Request $request)
    {
        $user = auth()->user();
        $data = ['name' => 'Новая презентация'];
        if ($request->has('folder_id')) {
            $data['folder_id'] = $request->input('folder_id');
        }
        $presentation = Presentation::factory()
        ->for($user)
        ->withSlide()
        ->create($data);

        return redirect()->route('editor', ['id' => $presentation->id]);
    }

    public function showLivePresentation(Request $request, $presentation_id)
    {
        $presentation = Presentation::with(['slides.type', 'slides.theme', 'shows'])->findOrFail($presentation_id);

        $url = env('APP_URL') . '/' .  $presentation->join_url;
        $qrCode = $this->generateQRCode($url);
        $show = $this->createShow($presentation);
        $show->state = 'lobby';
        $show->save();
        $slide_id = $request->input('slide_id');
        $slide = $presentation->slides()->with('theme')->findOrFail($slide_id);
        $slides = $presentation->slides;
        $slide_index = $slides->search(function ($s) use ($slide_id) {
            return $s->id == $slide_id;
        });
        broadcast(new ShowStarted($show->id));
        broadcast(new StartPresenting($presentation->id));

        return Inertia::render('LiveMode/LiveView', [
            'page' => 'lobby',
            'presentation' => $presentation,
            'slide' => $slide,
            'slide_index' => $slide_index,
            'join_url' => $url,
            'qrCode' => $qrCode
        ]);
    }
    
    private function generateJoinUrl() {
        return env('APP_URL') . '/' . strtoupper(Str::random(5));
    }

    private function generateQRCode($url) {
        $qrCode = QrCode::size(200)->generate($url);
        $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCode);

        return $qrCodeDataUri;
    }
    
    private function createShow($presentation) {
        $show = $presentation->shows()->first();
        if (!$show) {
            $show = $presentation->shows()->create();
        }

        return $show;
    }

    public function uploadProfilePhoto(Request $request)
    {;
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        
        $user = Auth::user();
        $avatar = $request->file('image');
        $filename = $user->id . '.' . $avatar->getClientOriginalExtension();
        $new_photo_path = $avatar->storeAs('avatars', $filename, 'images');
        
        $user->profile_photo_path = $new_photo_path;
        $user->save();

        return response()->json(['new_photo_path' => $new_photo_path]);
    }

    public function showSlide(Request $request, $presentation_id, $index)
    {
        $presentation = Presentation::with(['slides.type', 'slides.theme', 'shows'])->findOrFail($presentation_id);
        $slide = $presentation->slides()->with('theme')->get()[$index];
        $url = env('APP_URL') . '/' .  $presentation->join_url;
        $qrCode = $this->generateQRCode($url);
        $show = $presentation->shows()->first();
        $show->showSlide($slide);
        $show->state = 'lobby';
        $show->save();
        broadcast(new PrepareForSlide($show->id, $slide->id));
        broadcast(new StartPresenting($presentation->id));
        return Inertia::render('LiveMode/LiveView', [
            'page' => 'question',
            'presentation' => $presentation,
            'join_url' => $url,
            'qrCode' => $qrCode,
            'slide' => $slide,
            'slide_index' => (int) $index
        ]);
    }

    public function startSlide(Request $request)
    {
        $slide_id = $request->input('slide_id');
        $show_id = $request->input('show_id');
        $show = Show::findOrFail($show_id);
        $show->state = 'presenting';
        $show->save();
        broadcast(new ShowSlide($slide_id, $show_id));
    }

    public function finishSlide(Request $request)
    {
        $slide_id = $request->input('slide_id');
        $show_id = $request->input('show_id');
        $show = Show::findOrFail($show_id);
        $show->state = 'slide-finished';
        $show->save();
        broadcast(new FinishSlide($slide_id, $show_id));
    }
    
    public function checkAllPlayersAnswered(Request $request)
    {
        $players = $request->input('players');
        $slide_id = $request->input('slide_id');

        $slide = Slide::findOrFail($slide_id);

        return $slide->hasAllPlayersAnswered($players);
    }

    public function showLeaderBoard(Request $request)
    {
        $presentation_id = $request->route('id');
        $presentation = Presentation::with(['slides.type', 'slides.theme', 'shows'])->findOrFail($presentation_id);
        $url = env('APP_URL') . '/' .  $presentation->join_url;
        $qrCode = $this->generateQRCode($url);
        $show = $presentation->shows()->first();
        $show->state = 'leaderboard';
        $show->save();
        $slide = $presentation->slides()->with('theme')->latest()->first();
        $slideIndex = $presentation->slides()->count() - 1;
        return Inertia::render('LiveMode/LiveView', [
            'page' => 'leaderboard',
            'presentation' => $presentation,
            'join_url' => $url,
            'qrCode' => $qrCode,
            'slide' => $slide,
            'slide_index' => $slideIndex
        ]);
    }
}
