import COAI_Artifact from './artifact';

// Your code here
export default class InlineCitationArtifact extends COAI_Artifact {

    constructor(artifact) {
        super(artifact);
        //set the attributes of the artifact
        this.content_id = artifact.getAttribute('content_id');
    }

    //NOTE: content_supplied is passed by reference, and it maps content_id to the content object
    //NOTE: so is content_used, which we add each piece of content we use to
    render(content_supplied, content_used) {
        //var to hold label for this citation
        let lbl = 0;

        //see if this piece of content has already been cited
        if (content_supplied[this.content_id]?.label) {
            lbl = content_supplied[this.content_id].label;
        }
        //if it hasn't been, assign it a label
        else {
            //search through content_supplied for the largest label
            let largest = 0;
            for (let key in content_supplied) {
                if (content_supplied[key].label > largest) {
                    largest = content_supplied[key].label;
                }
            }
            lbl = largest + 1;
        }

        //check if this piece of content is in content_used
        let already_used = false;
        for (let i = 0; i < content_used.length; i++) {
            if (content_used[i].id === this.content_id) {
                already_used = true;
                break;
            }
        }

        //add it to the list of content used, if necessary
        if (!already_used) {
            content_used.push(
                content_supplied[this.content_id]
            );
        }


        //create a span
        const span = document.createElement('span');
        span.innerHTML = this.el.innerHTML;
        
        //create a link
        const a = document.createElement('a');
        a.href = content_supplied[this.content_id].url || '#';
        a.classList.add('contentoracle-inline_citation');
        a.target = '_blank';
        a.innerHTML = lbl;

        //set label on content supplied entry
        content_supplied[this.content_id].label = lbl;

        //add the link to the span
        span.appendChild(a);

        return span;
    }
}