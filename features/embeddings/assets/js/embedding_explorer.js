
document.addEventListener('DOMContentLoaded', function () {
    //get embeddings form
    let generate_embeddings_form = document.getElementById('coai_chat_bulk_generate_embeddings_form');

    //add event listener to the form for submit
    generate_embeddings_form.addEventListener('submit', async function (event) {
        event.preventDefault();

        //hide the success and error messages
        let success_msg = document.getElementById('coai_chat_bulk_embed_success_msg');
        success_msg.classList.add('coai_chat_bulk_generate_embeddings_hidden');

        let error_msg = document.getElementById('coai_chat_bulk_embed_error_msg');
        error_msg.classList.add('coai_chat_bulk_generate_embeddings_hidden');

        //get the selected option
        let selected_option = document.getElementById('bulk_generate_embeddings_select').value;

        //show the spinner
        let spinner = document.getElementById('coai_chat_bulk_generate_embeddings_spinner');
        spinner.classList.remove('coai_chat_bulk_generate_embeddings_hidden');
        spinner.classList.add('coai_chat_bulk_generate_embeddings_show');

        //hide the form
        generate_embeddings_form.classList.add('coai_chat_bulk_generate_embeddings_hidden');


        //submit ajax to the generate embeddings
        try {
            const result = await coai_generate_embeddings(selected_option);
            console.log(result, "result");
            //show the success message
            success_msg.classList.remove('coai_chat_bulk_generate_embeddings_hidden');

        } catch (error) {
            console.error(error, "error");
            //show the error message
            error_msg.classList.remove('coai_chat_bulk_generate_embeddings_hidden');
        } finally {
            //hide the spinner
            spinner.classList.remove('coai_chat_bulk_generate_embeddings_show');
            spinner.classList.add('coai_chat_bulk_generate_embeddings_hidden');
            //show the form
            generate_embeddings_form.classList.remove('coai_chat_bulk_generate_embeddings_hidden');
        }
    });

});