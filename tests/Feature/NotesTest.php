<?php

namespace Tests\Feature;

use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Note;
use OGame\Services\NoteService;
use Tests\AccountTestCase;

/**
 * Tests for the personal Notes feature, covering both NoteService and NotesController.
 *
 * Service:
 *  - createNoteForUser persists priority/subject/content tied to current user
 *  - getAllNotesForUser returns the user's notes ordered by created_at DESC
 *  - getAllNotesForUser does NOT leak other users' notes
 *  - getNoteById returns the note when owned, null otherwise
 *  - updateNoteForUser updates the row
 *  - deleteAllNotesForUser wipes only the current user's notes
 *  - deleteMarkedNotes removes only the supplied ids belonging to the user
 *  - noteExistsAndBelongsToUser reflects ownership
 *
 * HTTP:
 *  - GET /overlay/notes renders the overlay
 *  - POST /ajax/notes/create creates a note (returns success + new id)
 *  - POST /ajax/notes/create with id updates the existing note
 *  - POST /overlay/notes with delete method 2 wipes all notes
 *  - POST /overlay/notes with delete method 1 deletes only marked ids
 *  - GET /overlay/notes/view?id=X populates the form for editing
 */
class NotesTest extends AccountTestCase
{
    private NoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // NoteService depends on PlayerService — instantiate it manually for the current user.
        $playerServiceFactory = resolve(PlayerServiceFactory::class);
        $player = $playerServiceFactory->make($this->currentUserId, true);
        $this->service = new NoteService($player);

        // Defensive cleanup so each test starts from zero notes for the current user.
        Note::query()->where('user_id', $this->currentUserId)->delete();
    }

    // ── Service ──────────────────────────────────────────────────────────────

    public function testCreateNoteForUserPersistsAllFields(): void
    {
        $note = $this->service->createNoteForUser([
            'priority' => 1,
            'subject'  => 'Strategia attacco',
            'content'  => 'Inviare 200 incrociatori al sistema 4:271:8 alle 04:00.',
        ]);

        $this->assertSame($this->currentUserId, (int) $note->user_id);
        $this->assertSame(1, (int) $note->priority);
        $this->assertSame('Strategia attacco', $note->subject);
        $this->assertSame('Inviare 200 incrociatori al sistema 4:271:8 alle 04:00.', $note->content);

        $this->assertDatabaseHas('notes', [
            'id'      => $note->id,
            'user_id' => $this->currentUserId,
            'subject' => 'Strategia attacco',
        ]);
    }

    public function testGetAllNotesForUserReturnsRowsOrderedByCreatedAtDesc(): void
    {
        $first = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'A', 'content' => 'first']);
        // Bump time so the second row is unambiguously newer than the first.
        $this->travel(2)->seconds();
        $second = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'B', 'content' => 'second']);
        $this->travel(2)->seconds();
        $third = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'C', 'content' => 'third']);

        $notes = $this->service->getAllNotesForUser();
        $this->assertCount(3, $notes);
        $this->assertSame($third->id, $notes[0]->id);
        $this->assertSame($second->id, $notes[1]->id);
        $this->assertSame($first->id, $notes[2]->id);
    }

    public function testGetAllNotesForUserDoesNotLeakOtherUsersNotes(): void
    {
        // Create a foreign user's note directly.
        $otherUserId = $this->getSecondPlayerId();
        Note::create([
            'user_id'  => $otherUserId,
            'priority' => 2,
            'subject'  => 'foreign',
            'content'  => 'should-not-leak',
        ]);

        // The current user has zero notes of their own.
        $this->assertEmpty($this->service->getAllNotesForUser());
    }

    public function testGetNoteByIdReturnsOwnedNoteAndNullForForeign(): void
    {
        $own = $this->service->createNoteForUser(['priority' => 3, 'subject' => 'own', 'content' => '...']);

        $foreign = Note::create([
            'user_id'  => $this->getSecondPlayerId(),
            'priority' => 1,
            'subject'  => 'foreign',
            'content'  => 'x',
        ]);

        $this->assertNotNull($this->service->getNoteById($own->id));
        $this->assertNull($this->service->getNoteById($foreign->id), 'Foreign notes must not be returned.');
    }

    public function testUpdateNoteForUserChangesContent(): void
    {
        $note = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'old', 'content' => 'old text']);

        $this->service->updateNoteForUser($note->id, [
            'priority' => 1,
            'subject'  => 'new',
            'content'  => 'new text',
        ]);

        $row = Note::find($note->id);
        $this->assertSame(1, (int) $row->priority);
        $this->assertSame('new', $row->subject);
        $this->assertSame('new text', $row->content);
    }

    public function testDeleteAllNotesForUserOnlyDeletesOwnNotes(): void
    {
        $this->service->createNoteForUser(['priority' => 2, 'subject' => 'a', 'content' => 'a']);
        $this->service->createNoteForUser(['priority' => 2, 'subject' => 'b', 'content' => 'b']);

        $foreignUserId = $this->getSecondPlayerId();
        Note::create([
            'user_id'  => $foreignUserId,
            'priority' => 2,
            'subject'  => 'foreign',
            'content'  => 'keep-me',
        ]);

        $this->service->deleteAllNotesForUser();

        $this->assertSame(0, Note::query()->where('user_id', $this->currentUserId)->count());
        $this->assertSame(1, Note::query()->where('user_id', $foreignUserId)
            ->where('subject', 'foreign')->count(), 'Foreign notes must NOT be deleted.');
    }

    public function testDeleteMarkedNotesRemovesOnlySuppliedOwnIds(): void
    {
        $a = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'a', 'content' => 'a']);
        $b = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'b', 'content' => 'b']);
        $c = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'c', 'content' => 'c']);

        $this->service->deleteMarkedNotes([$a->id, $c->id]);

        $this->assertNull(Note::find($a->id));
        $this->assertNotNull(Note::find($b->id), 'Unselected own notes must survive.');
        $this->assertNull(Note::find($c->id));
    }

    public function testDeleteMarkedNotesDoesNotTouchForeignNotesEvenWhenIdSupplied(): void
    {
        $foreignUserId = $this->getSecondPlayerId();
        $foreign = Note::create([
            'user_id'  => $foreignUserId,
            'priority' => 2,
            'subject'  => 'foreign',
            'content'  => 'keep',
        ]);

        // The current user attempts to delete the foreign note via its id.
        $this->service->deleteMarkedNotes([$foreign->id]);

        $this->assertNotNull(Note::find($foreign->id), 'Foreign notes must be unaffected by markedDelete.');
    }

    public function testNoteExistsAndBelongsToUserReflectsOwnership(): void
    {
        $own = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'mine', 'content' => '.']);
        $this->assertTrue($this->service->noteExistsAndBelongsToUser($own->id));
        $this->assertFalse($this->service->noteExistsAndBelongsToUser(99999999));
    }

    // ── HTTP ─────────────────────────────────────────────────────────────────

    public function testOverlayGetReturnsTwoHundred(): void
    {
        $response = $this->get(route('notes.overlay'));
        $response->assertStatus(200);
    }

    public function testAjaxCreateInsertsNote(): void
    {
        $response = $this->postJson(route('notes.ajax.create'), [
            'noticePrio'    => 1,
            'noticeSubject' => 'subject1',
            'noticeText'    => 'body1',
        ]);
        $response->assertStatus(200);
        $payload = $response->json();
        $this->assertNotNull($payload['id']);
        $this->assertNull($payload['error']);
        $this->assertNotEmpty($payload['success']);

        $this->assertDatabaseHas('notes', [
            'id'       => $payload['id'],
            'user_id'  => $this->currentUserId,
            'subject'  => 'subject1',
            'content'  => 'body1',
            'priority' => 1,
        ]);
    }

    public function testAjaxCreateUpdatesExistingNoteWhenIdProvided(): void
    {
        $note = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'old', 'content' => 'old']);

        $response = $this->postJson(route('notes.ajax.create'), [
            'id'            => $note->id,
            'noticePrio'    => 3,
            'noticeSubject' => 'new',
            'noticeText'    => 'new body',
        ]);
        $response->assertStatus(200);

        $row = Note::find($note->id);
        $this->assertSame(3, (int) $row->priority);
        $this->assertSame('new', $row->subject);
        $this->assertSame('new body', $row->content);
    }

    public function testAjaxCreateRejectsForeignIdViaValidation(): void
    {
        $foreign = Note::create([
            'user_id'  => $this->getSecondPlayerId(),
            'priority' => 1,
            'subject'  => 'foreign',
            'content'  => 'x',
        ]);

        $response = $this->postJson(route('notes.ajax.create'), [
            'id'            => $foreign->id,
            'noticePrio'    => 1,
            'noticeSubject' => 'hijack',
            'noticeText'    => 'attempt',
        ]);
        $response->assertStatus(422); // validation closure rejects the id
    }

    public function testOverlayPostDeleteAllRemovesAllOwnNotes(): void
    {
        $this->service->createNoteForUser(['priority' => 2, 'subject' => 'a', 'content' => 'a']);
        $this->service->createNoteForUser(['priority' => 2, 'subject' => 'b', 'content' => 'b']);

        $response = $this->post(route('notes.overlay'), [
            'noticeDeleteMethode' => '2',
        ]);
        $response->assertStatus(200);

        $this->assertSame(0, Note::query()->where('user_id', $this->currentUserId)->count());
    }

    public function testOverlayPostDeleteMarkedRemovesOnlySuppliedIds(): void
    {
        $a = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'a', 'content' => 'a']);
        $b = $this->service->createNoteForUser(['priority' => 2, 'subject' => 'b', 'content' => 'b']);

        $response = $this->post(route('notes.overlay'), [
            'noticeDeleteMethode' => '1',
            'delIds'              => [$a->id],
        ]);
        $response->assertStatus(200);

        $this->assertNull(Note::find($a->id));
        $this->assertNotNull(Note::find($b->id));
    }

    public function testViewPopulatesFormForEditing(): void
    {
        $note = $this->service->createNoteForUser([
            'priority' => 3,
            'subject'  => 'edit-me',
            'content'  => 'edit-content',
        ]);

        $response = $this->get(route('notes.view', ['id' => $note->id]));
        $response->assertStatus(200);
        $response->assertViewHas('noteId', $note->id);
        $response->assertViewHas('priority', 3);
        $response->assertViewHas('subject', 'edit-me');
        $response->assertViewHas('content', 'edit-content');
    }

    public function testViewWithoutIdReturnsBlankForm(): void
    {
        $response = $this->get(route('notes.view'));
        $response->assertStatus(200);
        $response->assertViewHas('noteId', 0);
        $response->assertViewHas('priority', 2);
        $response->assertViewHas('subject', '');
        $response->assertViewHas('content', '');
    }
}
