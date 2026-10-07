
export default class COAI_Artifact{
    constructor(artifact){
        if (new.target === COAI_Artifact) {
            throw new TypeError("Cannot construct COAI_Artifact instances directly");
        }

        //save the input artifact
        this.el = artifact;
    }

    //render the artifact
    static render() {
        throw new Error('render method must be implemented');
    }
}