
//this function calls the generate embeddings route
async function coai_generate_embeddings(_for) {

    //post a request to the bulk generate embeddings route
    const embed_url = contentoracle_ai_chat_embeddings.api_base_url + 'contentoracle-ai-chat/v1/content-embed';

    //prepare the request body
    const body = {
        for: _for
    }
    console.log(JSON.stringify(body), "body");

    //send the request using xmlhttprequest with a very long timeout
    const xhr = new XMLHttpRequest();
    xhr.open('POST', embed_url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.timeout = 100000;
    xhr.send(JSON.stringify(body));

    xhr.onload = function () {
        console.log(xhr.responseText, "response");
    }


    //handle the response by refreshing the page
    //window.location.reload();

}