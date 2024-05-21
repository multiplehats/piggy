import { api } from "$lib/config/api";

class PiggyService {
    constructor() {
        console.log('PiggyService constructor');
    }
}

export const piggy = new PiggyService();