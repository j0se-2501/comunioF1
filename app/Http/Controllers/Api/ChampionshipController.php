<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipUser;
use App\Models\ScoringSystem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChampionshipController extends Controller
{
    /**
     * Listar los championships donde participa o administra el user
     */
    public function index()
    {
        $user = Auth::user();

        $championships = $user->championships()
            ->with('season')
            ->get()
            ->merge($user->championshipsOwned()->with('season')->get())
            ->unique('id')
            ->values();

        return response()->json($championships);
    }

    /**
     * Crear un nuevo championship
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Limite se calcula solo con campeonatos donde no está baneado
        $activeUserChampCount = $user->championships()
            ->wherePivot('is_banned', false)
            ->count();

        if ($activeUserChampCount >= 5) {
            return response()->json([
                'message' => 'No puedes estar en mas de 5 campeonatos a la vez'
            ], 403);
        }

        $data = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'name'      => 'required|string|max:255',
        ]);

        $championship = Championship::create([
            'admin_id'        => $user->id,
            'season_id'       => $data['season_id'],
            'name'            => $data['name'],
            'invitation_code' => Str::random(10),
            'status'          => 'active',
        ]);

        // Crear scoring system por defecto
        ScoringSystem::create([
            'championship_id'   => $championship->id,
            'points_p1'         => 10,
            'points_p2'         => 6,
            'points_p3'         => 4,
            'points_p4'         => 3,
            'points_p5'         => 2,
            'points_p6'         => 1,
            'points_pole'       => 3,
            'points_fastest_lap'=> 1,
            'points_last_place' => 3,
        ]);

        // El admin entra automáticamente como miembro
        $championship->users()->attach($user->id, [
            'total_points' => 0,
            'is_banned'    => false,
            'position'     => null,
        ]);

        return response()->json([
            'message' => 'Championship created successfully',
            'championship' => $championship
        ]);
    }

    /**
     * Ver detalles del championship
     */
    public function show($id)
    {
        $championship = Championship::with([
            'season',
            'users' => function ($q) {
                $q->withPivot('total_points', 'is_banned', 'position');
            },
            'scoringSystem'
        ])->findOrFail($id);

        return response()->json($championship);
    }

    /**
     * Editar championship (solo admin)
     */
    public function update(Request $request, $id)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'status'    => 'sometimes|string',
            'season_id' => 'sometimes|exists:seasons,id'
        ]);

        $championship->update($data);

        return response()->json([
            'message' => 'Championship updated successfully',
            'championship' => $championship
        ]);
    }

    /**
     * Ver código de invitación
     */
    public function invitationCode($id)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        return response()->json([
            'invitation_code' => $championship->invitation_code
        ]);
    }

    /**
     * Regenerar código de invitación
     */
    public function regenerateInvitationCode($id)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        $championship->update([
            'invitation_code' => Str::random(10)
        ]);

        return response()->json([
            'message' => 'Invitation code regenerated',
            'invitation_code' => $championship->invitation_code
        ]);
    }

    /**
     * Unirse a un championship usando código de invitación
     */
    public function join(Request $request)
    {
        $data = $request->validate([
            'invitation_code' => 'required|string'
        ]);

        $championship = Championship::where('invitation_code', $data['invitation_code'])->first();

        if (!$championship) {
            return response()->json(['message' => 'Invalid invitation code'], 404);
        }

        $user = Auth::user();

        // Verificar si está baneado
        $pivot = $championship->users()->where('user_id', $user->id)->first();

        if ($pivot && $pivot->pivot->is_banned) {
            return response()->json(['message' => 'You are banned from this championship'], 403);
        }

        // Si ya está unido, devolver ok
        if ($championship->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already joined'], 200);
        }

        $activeUserChampCount = $user->championships()
            ->wherePivot('is_banned', false)
            ->count();

        if ($activeUserChampCount >= 5) {
            return response()->json(['message' => 'No puedes estar en mas de 5 campeonatos a la vez'], 403);
        }

        $activeChampUsers = $championship->users()
            ->wherePivot('is_banned', false)
            ->count();

        if ($activeChampUsers >= 20) {
            return response()->json(['message' => 'El campeonato ya tiene el maximo de 20 usuarios'], 403);
        }

        $championship->users()->attach($user->id, [
            'total_points' => 0,
            'is_banned' => false,
            'position' => null
        ]);

        return response()->json([
            'message' => 'Joined championship successfully',
            'championship' => $championship
        ]);
    }

    /**
     * Abandonar championship
     */
    public function leave($id)
{
    $championship = Championship::findOrFail($id);
    $userId = Auth::id();

    // Si el que abandona es el admin, se elimina el campeonato entero
    if ($championship->admin_id == $userId) {
        $championship->delete();

        return response()->json([
            'message' => 'Campeonato eliminado por el administrador.'
        ]);
    }

    // Si no es admin, simplemente se desvincula del campeonato
    $championship->users()->detach($userId);

    return response()->json([
        'message' => 'Has abandonado el campeonato.'
    ]);
}


    /**
     * Banear usuario
     */
    public function banUser($id, $userId)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        $championship->users()->updateExistingPivot($userId, [
            'is_banned' => true
        ]);

        return response()->json(['message' => 'User banned']);
    }

    /**
     * Desbanear usuario
     */
    public function unbanUser($id, $userId)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        $championship->users()->updateExistingPivot($userId, [
            'is_banned' => false
        ]);

        return response()->json(['message' => 'User unbanned']);
    }

    /**
     * Listar miembros del championship
     */
    public function members($id)
    {
        $championship = Championship::findOrFail($id);

        $members = $championship
            ->users()
            ->withPivot(['total_points', 'is_banned', 'position'])
            ->get();

        return response()->json($members);
    }

    /**
     * Listar miembros no baneados
     */
    public function activeMembers($id)
    {
        $championship = Championship::findOrFail($id);

        $members = $championship
            ->users()
            ->wherePivot('is_banned', false)
            ->withPivot(['total_points', 'is_banned', 'position'])
            ->get();

        return response()->json($members);
    }

    /**
     * Listar miembros baneados
     */
    public function bannedMembers($id)
    {
        $championship = Championship::findOrFail($id);

        $members = $championship
            ->users()
            ->wherePivot('is_banned', true)
            ->withPivot(['total_points', 'is_banned', 'position'])
            ->get();

        return response()->json($members);
    }

    /**
     * Actualizar scoring system
     * (si no usas ScoringController independiente)
     */
    public function updateScoring(Request $request, $id)
    {
        $championship = Championship::findOrFail($id);

        $this->authorizeAdmin($championship);

        $data = $request->validate([
            'points_p1' => 'required|integer',
            'points_p2' => 'required|integer',
            'points_p3' => 'required|integer',
            'points_p4' => 'required|integer',
            'points_p5' => 'required|integer',
            'points_p6' => 'required|integer',
            'points_pole' => 'required|integer',
            'points_fastest_lap' => 'required|integer',
            'points_last_place' => 'required|integer',
        ]);

        $championship->scoringSystem->update($data);

        return response()->json([
            'message' => 'Scoring system updated successfully',
            'scoring_system' => $championship->scoringSystem
        ]);
    }

    /**
     * Verificar si el user autenticado es admin
     */
    private function authorizeAdmin(Championship $championship)
    {
        if ($championship->admin_id !== Auth::id()) {
            abort(403, 'Only the admin can perform this action');
        }
    }
}

