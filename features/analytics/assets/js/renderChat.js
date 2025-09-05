//function to render the markdown of an ai chat
function coai_chat_renderMd(markdown) {
    return window.marked.parse(markdown);
}

//main, where we call render functions
document.addEventListener('DOMContentLoaded', function () {

    //get all .coai_chat_message-content elements nested inside of 
    //coai_chat_chat-message coai_chat_assistant-message elements
    const assistant_messages = document.querySelectorAll('.coai_chat_chat-message.coai_chat_assistant-message .coai_chat_message-content');

    //render their content as markdown
    assistant_messages.forEach(message => {
        message.innerHTML = coai_chat_renderMd(message.innerHTML);
    });

});