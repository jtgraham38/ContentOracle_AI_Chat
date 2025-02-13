import COAI_Artifact from './artifact';

// Your code here
export default class InlineCitationArtifact extends COAI_Artifact {

    constructor(artifact) {
        super(artifact);
        //set the attributes of the artifact
        this.content_id = artifact.getAttribute('content_id');
    }

    //NOTE: content_supplied is passed by reference, and it maps content_id to the content object
    render(content_supplied) {
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