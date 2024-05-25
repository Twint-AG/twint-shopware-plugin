import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";

export default class PaymentStatusRefresh extends Plugin {

    static options = {
        pairingHash: null,
        interval: 1000,
    };

    count = 0;

    init(){
        this.checking = false;
        this.client = new HttpClient();

        this.checkStatus();
    }

    checkStatus(){
        if(this.checking || this.count > 10){
            return;
        }

        this.count++;
        this.checking = true;

        this.client.get('/payment/monitoring/' + this.options.pairingHash, (response) => {
            const data = JSON.parse(response);
            this.checking = false;
            if(data.completed){
                window.location.reload();
            } else {
                setTimeout(this.checkStatus.bind(this), this.options.interval);
            }
        });
    }
}
