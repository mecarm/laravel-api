<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Mail\CreatePostMail;
use App\Post;
use App\Http\Controllers\Controller;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;



class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //con la funzione with() recupero anche le informazioni contenuti nella tabella category richiamando la funzione del model
        $data = [
            'posts' => Post::with('category')->paginate(25) 
        ];

        return view('admin.posts.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $data = [
            'categories' => Category::All(),
            'tags' => Tag::All()
        ];

        return view('admin.posts.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        // dd($data);

        //validazionee
        $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        //Condizione if per controllare se l'immagine è stata caricata nell'input
        if(array_key_exists('image', $data)){
            $cover_url = Storage::put('post_covers', $data['image']);
            $data['cover'] = $cover_url;
        }

        $newPost = new Post();
        $newPost->fill($data);
        $newPost->save();

        //Controllo se esiste all'interno di data un array di nome tags (controllo se l'utente ha cliccato delle checkbox)
        if( array_key_exists( 'tags', $data ) ){
            $newPost->tags()->sync( $data['tags'] );
        }

        //INVIO MAIL DI CREAZIONE POST
        $mail = new CreatePostMail($newPost);
        $email_utente = Auth::user();
        Mail::to($email_utente)->send($mail);

        return redirect()->route('admin.posts.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $singolo_post = Post::findOrFail($id);

        return view('admin.posts.show', compact('singolo_post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::findOrFail($id);

        $categories = Category::All();

        $tags = Tag::all();

        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $singolo_post = Post::findOrFail($id);

        $singolo_post->update($data);

        //Controlla se l'utente ha cliccato o erano già selezionate delle checkbox
        if( array_key_exists( 'tags', $data ) ){
            $singolo_post->tags()->sync($data['tags']);
        }else{
            //Non ci sono checkbox selezionate
            $singolo_post->tags()->sync([]);
        }

        return redirect()->route('admin.posts.show', $singolo_post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $singolo_post = Post::findOrFail($id);
        if($singolo_post->cover){
            Storage::delete($singolo_post->cover);
        }
        $singolo_post->tags()->sync([]);
        $singolo_post->delete();

        return redirect()->route('admin.posts.index');
    }
}


