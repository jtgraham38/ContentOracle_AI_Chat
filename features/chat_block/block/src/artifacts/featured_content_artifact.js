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
    render(content_supplied, featured_content_border_classes, featured_content_button_classes) {        //create a container, with class coai_chat-featured_content
        const container = document.createElement('div');
        container.classList.add('coai_chat-featured_content');
        featured_content_border_classes.map((border_class) => {
            if (border_class)
                container.classList.add(border_class);
        });

        //create an inner container, with class coai_chat-featured_content_inner
        const inner_container = document.createElement('div');
        inner_container.classList.add('coai_chat-featured_content_inner');


        //create the cta
        const cta = document.createElement('p');
        cta.innerHTML = this.el.innerHTML;

        //if no link is present, then we shouldn't render any visible html
        if (!content_supplied[this.content_id]?.url) {
            return document.createElement('div');
        }

        //create a link button
        const btn = document.createElement('a');
        const btn_text = this.el.getAttribute('button_text');
        btn.href = content_supplied[this.content_id]?.url || '#';
        btn.innerText = btn_text || 'Go!';
        btn.target = '_blank';
        btn.classList.add('coai_chat-featured_content_btn');
        featured_content_button_classes.map((button_class) => {
            if (button_class)
                btn.classList.add(button_class);
        });


        //create an image for the featured image
        let img;
        if (content_supplied[this.content_id]?.image) {
            img = document.createElement('img');
            img.src = content_supplied[this.content_id]?.image || '';
            img.alt = content_supplied[this.content_id]?.title || '';
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