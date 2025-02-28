import COAI_Artifact from './artifact';

// Your code here
export default class FeaturedContentArtifact extends COAI_Artifact {

    constructor(artifact) {
        super(artifact)
        //set the attributes of the artifact
        this.content_id = artifact.getAttribute('content_id');
    }

    //render the artifact
    //NOTE: content_supplied is passed by reference, and it maps content_id to the content object
    render(content_supplied) {
        //create a container, with class coai_chat-featured_content
        const container = document.createElement('div');
        container.classList.add('coai_chat-featured_content');

        //create an inner container, with class coai_chat-featured_content_inner
        const inner_container = document.createElement('div');
        inner_container.classList.add('coai_chat-featured_content_inner');


        //create the cta
        const cta = document.createElement('p');
        cta.innerHTML = this.el.innerHTML;

        //create a link button
        const btn = document.createElement('a');
        const btn_text = this.el.getAttribute('button_text');
        btn.href = content_supplied[this.content_id].url || '#';
        btn.innerText = btn_text || 'Go!';
        btn.target = '_blank';
        
        //create an image for the featured image
        let img;
        if (content_supplied[this.content_id].image) {
            img = document.createElement('img');
            img.src = content_supplied[this.content_id].image;
            img.alt = content_supplied[this.content_id].title;
        }

        //build the card
        if (content_supplied[this.content_id].image) {
            inner_container.appendChild(img);
        }
        inner_container.appendChild(cta);
        container.appendChild(inner_container);
        container.appendChild(btn);

        //return the container
        return container;
    }
}